from hashlib import sha256
import hmac

user       = "<user>"         # The user id
publicKey  = "<public key>"   # The public key of the user
privateKey = "<secret value>" # The private key of the user
image      = "<image>"        # The image identifier

# Image transformations
query = [
    "t[]=thumbnail:width=40,height=40,fit=outbound",
    "t[]=border:width=3,height=3,color=000",
    "t[]=canvas:width=100,height=100,mode=center"
]

# Specify public key if it differs from user
if user != publicKey:
    query.append('publicKey=%s' % (publicKey))

# The URI
uri = "http://imbo/users/%s/images/%s?%s" % (user, image, "&".join(query))

# Generate the token
accessToken = hmac.new(privateKey, uri, sha256)

# Output the URI with the access token
print "%s&accessToken=%s" % (uri, accessToken.hexdigest())
