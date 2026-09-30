"""Test-only SMTP inbox. Bind loopback; never deliver messages to the Internet.
Run with Python <=3.11: python3 tests/smtp_capture.py /tmp/vg-test-mails.jsonl
"""
import asyncore
import json
import smtpd
import sys

class Capture(smtpd.SMTPServer):
    def process_message(self, peer, mailfrom, rcpttos, data, **kwargs):
        with open(sys.argv[1], 'a', encoding='utf-8') as stream:
            stream.write(json.dumps({'from': mailfrom, 'to': rcpttos,
                                     'message': data.decode('utf-8', 'replace')}) + '\n')

Capture(('127.0.0.1', 1025), None)
asyncore.loop()
