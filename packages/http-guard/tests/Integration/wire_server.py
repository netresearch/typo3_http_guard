#!/usr/bin/env python3
"""Synthetic HTTP/TLS target. Admin counters use a separate, uncounted port."""
import argparse
import base64
import collections
import http.server
import json
import socket
import ssl
import threading
import time

parser = argparse.ArgumentParser()
parser.add_argument('--label', required=True)
parser.add_argument('--cert-dir', required=True)
parser.add_argument('--bind', default='0.0.0.0')
parser.add_argument('--bind6', default='::')
parser.add_argument('--port-offset', type=int, default=0)
args = parser.parse_args()
lock = threading.Lock()
state = dict(tcp=0, closed=0, requests=0, active=0, max_active=0, aborted=0,
             paths=collections.Counter(), sni=[], clients=[])

class Target(http.server.ThreadingHTTPServer):
    daemon_threads = True
    def get_request(self):
        connection, address = super().get_request()
        with lock:
            state['tcp'] += 1
            state['active'] += 1
            state['max_active'] = max(state['max_active'], state['active'])
        if hasattr(self, 'tls_context'):
            try:
                connection = self.tls_context.wrap_socket(connection, server_side=True)
            except (ssl.SSLError, OSError):
                connection.close()
                with lock:
                    state['closed'] += 1
                    state['active'] -= 1
                raise
        return connection, address
    def close_request(self, request):
        try:
            super().close_request(request)
        finally:
            with lock:
                state['closed'] += 1
                state['active'] -= 1

class Target6(Target):
    address_family = socket.AF_INET6
    def server_bind(self):
        self.socket.setsockopt(socket.IPPROTO_IPV6, socket.IPV6_V6ONLY, 1)
        super().server_bind()

class Handler(http.server.BaseHTTPRequestHandler):
    protocol_version = 'HTTP/1.1'
    def log_message(self, *unused):
        pass
    def do_GET(self):
        self.respond()
    do_HEAD = do_POST = do_PUT = do_PATCH = do_DELETE = do_GET
    def respond(self):
        body = self.rfile.read(int(self.headers.get('Content-Length', '0')))
        with lock:
            state['requests'] += 1
            state['paths'][self.path] += 1
            peer = self.connection.getpeercert() if isinstance(self.connection, ssl.SSLSocket) else None
            if peer:
                state['clients'].append(peer.get('subject', []))
        if self.path.startswith('/redirect-same'):
            return self.redirect('/echo')
        if self.path.startswith('/redirect-cross'):
            return self.redirect('http://other.test:8090/echo')
        if self.path.startswith('/redirect-private'):
            return self.redirect('http://10.23.4.12:8090/echo' if self.path.startswith('/redirect-private-ip') else 'http://erp.test:8090/echo')
        if self.path.startswith('/redirect-downgrade'):
            return self.redirect('http://guard.test:8090/echo')
        if self.path.startswith('/redirect-post-get'):
            return self.redirect('/echo')
        if self.path.startswith('/loop'):
            return self.redirect('/loop')
        if self.path.startswith('/reset'):
            self.connection.shutdown(socket.SHUT_RDWR)
            self.close_connection = True
            return
        if self.path.startswith('/slow'):
            chunks = [b'first-', b'middle-', b'last']
            self.send_response(200)
            self.send_header('Content-Length', str(sum(map(len, chunks))))
            self.end_headers()
            try:
                for chunk in chunks:
                    self.wfile.write(chunk)
                    self.wfile.flush()
                    time.sleep(0.3)
            except (BrokenPipeError, ConnectionResetError):
                with lock:
                    state['aborted'] += 1
            return
        if self.path.startswith('/large'):
            self.send_response(200)
            self.send_header('Content-Length', str(4 * 1024 * 1024))
            self.end_headers()
            for unused in range(64):
                self.wfile.write(b'x' * 65536)
            self.wfile.flush()
            return
        if self.path.startswith('/timeout'):
            time.sleep(2)
        payload = json.dumps(dict(label=args.label, method=self.command, path=self.path,
                                  host=self.headers.get('Host'), headers=dict(self.headers),
                                  body=base64.b64encode(body).decode())).encode()
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(payload)))
        self.end_headers()
        if self.command != 'HEAD':
            try:
                self.wfile.write(payload)
            except (BrokenPipeError, ConnectionResetError):
                with lock:
                    state['aborted'] += 1
    def redirect(self, location):
        self.send_response(302)
        self.send_header('Location', location)
        self.send_header('Content-Length', '0')
        self.end_headers()

class Admin(http.server.BaseHTTPRequestHandler):
    def log_message(self, *unused):
        pass
    def do_GET(self):
        with lock:
            payload = json.dumps(state).encode()
        self.send_response(200)
        self.send_header('Content-Length', str(len(payload)))
        self.end_headers()
        self.wfile.write(payload)

def tls_server(port, require_client):
    server = Target((args.bind, port + args.port_offset), Handler)
    context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    context.load_cert_chain(args.cert_dir + '/server.crt', args.cert_dir + '/server.key')
    def sni(connection, name, unused):
        with lock:
            state['sni'].append(name)
    context.set_servername_callback(sni)
    if require_client:
        context.load_verify_locations(args.cert_dir + '/ca.crt')
        context.verify_mode = ssl.CERT_REQUIRED
    server.tls_context = context
    return server

servers = [Target((args.bind, 8090 + args.port_offset), Handler), tls_server(8443, False), tls_server(8444, True),
           http.server.ThreadingHTTPServer((args.bind, 8091 + args.port_offset), Admin)]
servers.append(Target6((args.bind6, 8090 + args.port_offset), Handler))
for server in servers:
    threading.Thread(target=server.serve_forever, daemon=True).start()
threading.Event().wait()
