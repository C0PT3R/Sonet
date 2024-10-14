# Sonet (work in progress)

### A simple PHP framework for creating websites and/or web APIs.
It supports routing of HTTP requests with methods GET, POST, PUT and DELETE.

## Main concepts:
- Application: 
- VirtualPath: An extended path that can contain variables, options and aliases.
- Router: An object that contains Routes and is mounted to a VirtualPath. You can have as many as you want.
- Route: An object created by a Router and is mounted onto it.
- Request: A predefined object that contains information about the requested resource.
- Response: A predefined object that contains information about the response to be sent.
- Handler: A user defined callable that accepts Request and Response as parameters. Its role is to handle a Route or an Status.
