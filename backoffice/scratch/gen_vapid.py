import base64
from cryptography.hazmat.primitives.asymmetric import ec
from cryptography.hazmat.primitives import serialization

def b64url(data):
    return base64.urlsafe_b64encode(data).decode('utf-8').rstrip('=')

try:
    private_key = ec.generate_private_key(ec.SECP256R1())
    private_numbers = private_key.private_numbers()
    priv_bytes = private_numbers.private_value.to_bytes(32, byteorder='big')
    
    public_key = private_key.public_key()
    public_numbers = public_key.public_numbers()
    pub_bytes = b'\x04' + public_numbers.x.to_bytes(32, byteorder='big') + public_numbers.y.to_bytes(32, byteorder='big')
    
    print("VAPID_PUBLIC_KEY=" + b64url(pub_bytes))
    print("VAPID_PRIVATE_KEY=" + b64url(priv_bytes))
except Exception as e:
    print("Error:", e)
