# Sonet (work in progress)

### A simple PHP framework for creating websites and/or web APIs.
It supports routing of HTTP requests with methods GET, POST, PUT and DELETE.
Other things too... maybe one day I'll make a doc for it.

## Main concepts:
- Application: A global container for all application data. Is also a Router itself (main application Router).
- VirtualPath: An extended path that can contain variables, options and aliases.
- Router: An object that contains Routes and is mounted to a VirtualPath. You can have as many as you want.
- Route: An object created by a Router and mounted onto it.
- StatusEvent: An event that is triggered when certain HTTP statuses are encountered.
- Request: A predefined object that contains information about the requested resource.
- Response: A predefined object that contains information about the response to be sent.
- Handler: A user defined callable that accepts Request and Response as parameters. It can be assigned to a Route or a StatusEvent.
