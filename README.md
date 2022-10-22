## About

Web-based GoogleCast devices explorer and monitor.

Based on https://github.com/ChrisRidings/CastV2inPHP for the MDNS bit.

Uses https://github.com/mohsen1/json-formatter-js for detailed output of device status.

## How it works

- Step 1: uses mdns discovery to find Google Cast devices on your network
- Step 2: queries each device discovery URL to get its status
- Step 3: ???
- Step 4: profit... I mean, shows a nice web UI with the result

## How to use it

clone the repo and use your favourite PHP web server to host
