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

### Example 02

Example 02 implements a simple ```Hello World``` process that prints the message via a ```CPPHPExecutionTask```.

![The BPMN diagram of Example 02.](/examples/example-02/example02.svg "The BPMN diagram of Example 02")

The PHP code being implemented in the task is very simple:
```php
echo "Hello World" . PHP_EOL;
```

Output:
```
Hello World
```

### Example 03

Example 03 implements concurrency between two tasks.

![The BPMN diagram of Example 03.](/examples/example-03/example03.svg "The BPMN diagram of Example 03")

**Note:** Currently, it is not supported to share data information between two concurrent tasks. 
Data information can only be propagated through sequence and message flows. *However, we are currently working on
supporting this in ongoing research.*

Output:
```
Hello World 1
Hello World 2
```

or

```
Hello World 2
Hello World 1
```

### Example 04

Example 04 implements a simple decision by exclusive gateways.
This example gets a little bit more challenging at the first view since it requires a data information ```x``` being 
passed between the tasks to perform a decision. 
For this reason, ```CPPHPExecutionTask``` implements a simple PHP script requesting an integer from the user (by CLI) 
and storing it in ```x```. 
To allow other tasks to access ```x```, this is marked as exported:

```PHP
$x = intval(readline("Please enter an integer: ")); 
$this->export("x"); // Export the local variable "x"
```

![The BPMN diagram of Example 04.](/examples/example-04/example04.svg "The BPMN diagram of Example 04")

The diverging exclusive gateway uses ```x``` in a condition (```x > 5```). 
The condition in *Progression* is implemented relatively simple: Data information are referred with ```{}```, e.g., 
```{x} > 5``` is the implementation of the condition. 
You can also use commands such as ```exists(x)``` to check if a data information (with name ```x```) is currently 
defined or ```hasErrors``` as a build in function if any task has thrown an error.

The both other ```CPPHPExecutionTask``` in the process model gives information about ```x```:

```PHP
// PHP Script Execution 2
echo $x . " is greater than 5" . PHP_EOL;
```

```PHP
// PHP Script Execution 3
echo $x . " is lower or equal to 5" . PHP_EOL;
```

As you can see, access data information / variables that are within the context is very simple.

**Note:** If you change a variable in a context, this change is made local only except you export the variable!

Output:
```
Please enter an integer: 3
3 is lower or equal to 5
```

### Example 05

*Progression* supports message/signal/event exchanges between process models. 
Example 05 implements a simple message exchange, in which the *Main Process* requests its *Sidekick* process model
to share the data information ```x```. 
Similarly to Example 04, ```x``` is used in a condition where *PHP Script Execution 1*, *3*, and *4* are implemented 
equally to that of Example 04.

![The BPMN diagram of Example 05.](/examples/example-05/example05.svg "The BPMN diagram of Example 05")

Output:
```
I call my sidekick to ask for x
Please enter an integer: 10
10 is greater than 5
```

### Example 06

The main difference of *Progression* regarding other workflow management systems is that it only supports 
*sound* and *acyclic* process models. 
However, it is still able to execute cyclic process models by performing loop decomposition.

**Note:** Currently, loop decomposition must be performed by hand *but an automatic decomposition is still in progress.*

This is the original model being implemented in Example 06:

![The BPMN diagram of Example 06.](/examples/example-06/example06.svg "The BPMN diagram of Example 06")

After performing loop decomposition this becomes:

![The BPMN diagram of Example 06 after loop decomposition.](/examples/example-06/example06-decomposed.svg 
"The BPMN diagram of Example 06 after loop decomposition")

**Note:** The *Bus* pool (with its process model) is unnecessary in *Progression*, however, BPMN demands for it as 
events cannot throw messages to the same process model - even if there are different process instances.

*PHP Script Execution 1* asks for an integer to specify the number of iterations:

```PHP
$this->export("x");
$x = intval(readline("Please enter the number of iterations of the loop: "));
```

*PHP Script Execution 2* (in the *Main* pool as well as in the *Loop* pool) informs the user about the iterations left:

```PHP
echo $x . " iterations remain." . PHP_EOL; 
$x--; 
$this->export("x");
```

Output:
```
Please enter the number of iterations of the loop: 3
3 iterations remain.
2 iterations remain.
1 iterations remain.
0 iterations remain.
```

## Contributing

If you want to contribute to *Progression*, please feel free to send a merge request.

## Authors and acknowledgment

*Progression* was created and developed by Dr. Thomas M. Prinz from the Friedrich Schiller University Jena.

## License
GNU GPLv3

## Project status

The project is currently under development but practically used in the *Coast* system of the Friedrich Schiller 
University Jena.