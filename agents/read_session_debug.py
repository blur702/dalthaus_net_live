import paramiko
import os
from dotenv import load_dotenv

load_dotenv('../.env')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(
    'mi3-cl9-its2.a2hosting.com',
    port=7822,
    username=os.getenv('SSH_USER'),
    password=os.getenv('SSH_PASS')
)

sftp = ssh.open_sftp()
try:
    f = sftp.file('/home/dalthaus/public_html/session_debug.txt', 'r')
    print(f.read().decode())
    f.close()
except Exception as e:
    print(f"Error: {e}")

sftp.close()
ssh.close()
