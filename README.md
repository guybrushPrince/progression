# Coast Progression

## Installation

The following steps are recommended to run *Progression* in a singleton mode 
(i.e., without surrounding environment):

1. Install (if not available) a web server (e.g., Apache) with a PHP version > 8.2.0.
   Windows users can download XAMPP to get this step done fast.
2. Clone this repository into your web server html documents folder
   (e.g., ```htdocs``` in XAMPP).
3. Switch to a terminal or CLI and go to the folder where you have cloned *Progression*.
4. In the uppermost folder, perform 

   ```shell
   php install.php
   ```
5. *Progression* was successfully installed.
6. If you want to perform a *Progression Tick*, please run from the folder ```htdocs```:
   ```shell
   php run.php
   ```
   It is recommended to install a CRON job (https://en.wikipedia.org/wiki/Cron) for doing this 
   (or a similar mechanism on Windows systems.)

**Note:** It is strongly recommended to implement an own persistent layer as the default implementation
of *Progression* is somewhat slow.

## Examples

You will find some examples in the folder ```examples```. 
Please be aware that ```example-01``` is not recommended as it requires 
some external classes.

To run an example on *Progression*, please perform the following steps:
1. Load the process model into *Progression* (must only done once):

   ```shell
   php example02.php
   ```
2. If you want to create an instance of the process model, then run:

   ```shell
   php example02-run.php
   ```
3. You find the BPMN models in the directory.   

## Contributing

If you want to contribute to *Progression*, please feel free to send a merge request.

## Authors and acknowledgment

*Progression* was created and developed by Dr. Thomas M. Prinz from the Friedrich Schiller University Jena.

## License
GNU GPLv3

## Project status

The project is currently under development but practically used in the *Coast* system of the Friedrich Schiller 
University Jena.