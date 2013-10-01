import hmac, requests
from hashlib import sha256
from datetime import datetime

publicKey  = "<user>"                                         # The public key of the user
privateKey = "<secret value>"                                 # The private key of the user
timestamp  = datetime.utcnow().strftime("%Y-%m-%dT%H:%M:%SZ") # Current timestamp (UTC)
image      = "<image>"                                        # The image identifier
method     = "DELETE"                                         # HTTP method to use

# The URI
uri = "http://imbo/users/%s/images/%s" % (publicKey, image)

# Data for the hash
data = "|".join([method, uri, publicKey, timestamp])

# Generate the token
signature = hmac.new(privateKey, data, sha256).hexdigest()

# Request the URI
response = requests.delete(uri, headers = {
    "X-Imbo-Authenticate-Signature": signature,
    "X-Imbo-Authenticate-Timestamp": timestamp
})
