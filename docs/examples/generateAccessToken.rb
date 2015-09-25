require "digest"

user       = "<user>"         # User id
publicKey  = "<public key>"   # The public key of the user
privateKey = "<secret value>" # The private key of the user
image      = "<image>"        # The image identifier

# Image transformations
queryParts = [
    "t[]=thumbnail:width=40,height=40,fit=outbound",
    "t[]=border:width=3,height=3,color=000",
    "t[]=canvas:width=100,height=100,mode=center"
]

# Specify public key if it differs from user
if user != publicKey
    queryParts.push("publicKey=#{publicKey}")
end

# Join the query parts
query = queryParts.join("&")

# The URI
uri = "http://imbo/users/#{user}/images/#{image}?#{query}"

# Generate the token
accessToken = Digest::HMAC.hexdigest(uri, privateKey, Digest::SHA256)

# Output the URI with the access token
puts "#{uri}&accessToken=#{accessToken}"
