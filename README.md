Watts Up? .net Plugin for EmonCMS
=================================

This plugin receives Watts Up? HTTP posts and converts them to
emoncms format.


Install
-------

Installation is pretty simple. You just need clone this repository
inside of the `Modules` folder wherever emoncms is installed.
Make sure the folder is called `wattsup`, however, so that emoncms
can find the paths correctly.

    cd Modules
    git clone https://github.com/lab11/emoncms-wattsup wattsup


Configure Watts Up? .net
-----

Once emoncms is ready to receive the data, the Watts Up? needs to transmit
to the correct location. This means setting four values in the Watts Up?.
[This guide](https://www.wattsupmeters.com/secure/downloads/CommunicationsProtocol090824.pdf)
describes how to, but there are libraries that exist to make it easier.

- **POST Host**:  <server> (example: emoncms.org)
- **POST Port**:  80
- **POST File**:  /wattsup/post.text
- **User Agent**: <API key>

You can set this with python and [this library](https://github.com/lab11/wattsup).
Clone the library and then

    ./wattsup.py -p /dev/ttyUSB<index> -n <server> 80 /wattsup/post.text -u <API key>


Note: the Watts Up? has a limit to the number of characters that can be in
the "POST File" section (the limit is 40). That means we can't use a standard
way of transmitting the API key. To work around this limit, we put the
API key in the user agent string, because that isn't being used for anything.
This isn't ideal, but it works without having to modify the base emoncms
libraries.
