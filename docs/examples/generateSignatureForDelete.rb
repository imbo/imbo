require "digest"
require "httpclient"

publicKey  = "<user>"                                    # The public key of the user
privateKey = "<secret value>"                            # The private key of the user
timestamp  = Time.now.utc.strftime("%Y-%m-%dT%H:%M:%SZ") # Current timestamp (UTC)
image      = "<image>"                                   # The image identifier
method     = "DELETE"                                    # HTTP method to use

# The URI
uri = "http://imbo/users/#{publicKey}/images/#{image}"

# Data for the hash
data = [method, uri, publicKey, timestamp].join("|")

# Generate the token
signature = Digest::HMAC.hexdigest(data, privateKey, Digest::SHA256)

# Request the URI
client = HTTPClient.new
response = client.delete(uri, {}, {
    "X-Imbo-Authenticate-Signature" => signature,
    "X-Imbo-Authenticate-Timestamp" => timestamp
})
