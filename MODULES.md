# Modules

Adding new features to Silo is handled by creating a Module.

It regroups in a single codebase the server and the client code needed to run the feature. It leverages the Silo core for usual components, and declare suitable dependencies.

It should be structured as follows:

* **client** React code for the frontend part
* **doc** Module's documentation
* **features** Behat tests for backend
* **server** Silex provider code for the backend part
* composer.json
* package.json
