## Documentation of Project Implementation for IPP 2022/2023
### Name and surname: David Novak
### Login: xnovak2r

## Script usage & STATP
The project can either write a short usage help (argument --help or -H) or parse the IPPcode23 code taken as input from standard input. Since project doesn't include the STATP extension, using any other argument(s) will result in error.

## NVP
There are no classes and methods used in the script. However, even while only using functions, the goal was to adhere to the Single Responsibility Principle as much as possible.

The functions are relatively simple, have a single purpose, and their names are appropriate for the purpose they are trying to accomplish.

## Main script body
After checking the script arguments, the base for XML is created using PHP DOM. Following the DOM document creation, variables containing regex expressions, opcode counter, and boolean checking whether the script has yet gotten the header are declared and initialized.

Now the script enters the finite-state machine (FSM) part, which mostly consists of a while loop and a switch statement. The input is read line by line using fgets function, the function **strip_line** is called and the script checks for the presence of the IPPcode23 header if there is none yet. The script tries to match the content of the line to a known opcode. If successful, it checks the opcode arguments with **opcode_argument_check** function.

If opcode arguments match the desired type, the **remove_special_chars** function is called, a new node is appended to the existing XML, and the opcode counter is increased. In case of an empty line, the script skips the counter increase and continues with the following line.

After successfully parsing all lines of code, the XML is printed to STDOUT.

### Functions
* **opcode_argument_check($arg) : int**
  * Function checks the type and validity of an argument
  * `$arg` is argument that needs checking 
* **validate_NS_constant($type, $arg) : int**
  * Function checks if a non-string constant is valid
  * `$type` is the part of constant before @ and `$arg` is the part of constant after @
* **remove_special_chars($string) : string**
  * Function returns string with special characters replaced by their xml friendly equivalent
  * `$string` is the string with possible special characters
* **strip_line($line) : string**
  * Function gets rid of comments and unnecessary white spaces
  * `$line` is the line of IPPcode23 code that might contain unnecessary white spaces or comments

## Testing
The script was tested with tests provided by the school as well as tests provided by the student body. Testing passed on both **Fedora OS**, where the script has been developed and school server **Merlin**, with **none** of the almost 4400 tests failing.
