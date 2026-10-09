import http.server
import json
import os
import socketserver
import threading

lock = threading.Lock()
state = {'tcpAccepts': 0, 'counterRequests': 0, 'requests': 0, 'paths': {}, 'headers': []}

class Handler(http.server.BaseHTTPRequestHandler):
    protocol_version = 'HTTP/1.1'

    def do_HEAD(self):
        self.do_GET(head=True)

    def do_GET(self, head=False):
        if self.path == '/counters':
            with lock:
                state['counterRequests'] += 1
                result = dict(state)
                result['targetTcpAccepts'] = state['tcpAccepts'] - state['counterRequests']
                body = json.dumps(result).encode()
            self.send_response(200)
        else:
            headers = {name: self.headers.get(name) for name in ('Host', 'Authorization', 'Cookie', 'User-Agent')}
            with lock:
                state['requests'] += 1
                state['paths'][self.path] = state['paths'].get(self.path, 0) + 1
                state['headers'].append(headers)
            body = json.dumps({'server': os.environ['WIRE_ID'], 'path': self.path, 'headers': headers}).encode()
            if self.path == '/redirect':
                self.send_response(307)
                self.send_header('Location', '/b')
            elif self.path == '/cross-redirect':
                self.send_response(302)
                self.send_header('Location', 'http://guard2.test:8080/b')
            else:
                self.send_response(200)
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        if not head:
            self.wfile.write(body)

    def log_message(self, fmt, *args):
        pass

class Server(socketserver.ThreadingMixIn, http.server.HTTPServer):
    daemon_threads = True

    def get_request(self):
        sock, address = super().get_request()
        with lock:
            state['tcpAccepts'] += 1
        return sock, address

Server(('0.0.0.0', 8080), Handler).serve_forever()
