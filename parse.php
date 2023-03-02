<?php
# Implementace 1. úlohy do IPP 2022/2023
# Jméno a příjmení: David Novák
# Login: xnovak2r

ini_set('display_errors', 'stderr');

if ($argc > 2)
    exit(10);
elseif ($argc > 1) {
    if ($argv[1] == "--help") {
        echo ("Usage: php8.1 ./parse.php <InputFile\n");
        echo ("InputFile header: .IPPcode23\n");
        exit(0);
    }
    exit(10);
}

# creating new DOM document
$xml = new DOMDocument('1.0', "UTF-8");
$xml->formatOutput = true;
$xml_program = $xml->createElement("program");
$xml->appendChild($xml_program);
$xml_program->setAttribute("language", "IPPcode23");

# declaration of global variables - header, counter and regex expressions
$no_identifier = true;
$counter = 1;
$label = "/^[\pL_\-$&%*!?][\pL_\-$&%*!?0-9]*$/u";
$variable = "/^(LF|GF|TF)@[\pL_\-$&%*!?][\pL_\-$&%*!?0-9]*$/u";
$constant_no_str = "/^(int|bool|nil)@.+/u";
//$str = "/^string@(?!.*\\\d{0,2}[^\d]).*/u";
$str = "/^string@(([^\s\#\\\\]|\\\\[0-9]{3})*$)/u";
$type = "/^(int|bool|string|nil)$/";

#################### main program parsing loop #######################
while ($line = fgets(STDIN)){
    $line = preg_replace('/\s*#.*/', '', $line);        # delete comments and whitespaces before and after comment
    $line = preg_replace('/\s+/', ' ', $line);          # replace all whitespaces with 1 space
    $line = trim(preg_replace('/\s+/', ' ', $line));    # delete space from the start and end of the line
    $line = explode(' ', $line);

    # if there is no header:
    #   1) there is header on current line
    #   2) the line is empty (empty line or deleted comment)
    #   3) there is something in input file before header -> ERROR
    if ($no_identifier){
        # "\s*.IPPcode23\n" => ".IPPcode23 "
        # line[0]==IPPcode23;line[1]==' '
        if (strcasecmp($line[0], ".IPPcode23") == 0) {
            $no_identifier = false;
            continue;
        }
        elseif ($line[0] == "")   # "\s*#comment" | "\s+" => ""
            continue;
        else
            exit(21);
    }

    ############    IPPcode23 OPCODES     #######################
    #############################################################
    # 0 Operands                                                #
    #       CREATEFRAME, PUSHFRAME, POPFRAME, RETURN, BREAK     #
    # 1 Operands                                                #
    #   <var>                                                   #
    #       DEFVAR, POPS                                        #
    #   <label>                                                 #
    #       CALL, LABEL, JUMP                                   #
    #   <symb>                                                  #
    #       PUSHS, WRITE, EXIT, DPRINT                          #
    # 2 Operands                                                #
    #   <var> <symb>                                            #
    #       MOVE, INT2CHAR, STRLEN, TYPE, NOT                   #
    #   <var> <type>                                            #
    #       READ                                                #
    # 3 Operands                                                #
    #   <var> <symb1> <symb2>                                   #
    #       ADD, SUB, MUL, IDIV, LT, GT, EQ, AND, OR,           #
    #       STRI2INT, CONCAT, GETCHAR, SETCHAR                  #
    #   <label> <symb1> <symb2>                                 #
    #       JUMPIFEQ, JUMPIFNEQ                                 #
    #############################################################

    $line[0] = strtoupper($line[0]);
    switch ($line[0]){
        case 'CREATEFRAME':
        case 'PUSHFRAME':
        case 'POPFRAME':
        case 'RETURN':
        case 'BREAK':
            if (count($line) != 1)
                exit(23);
            $instruction = $xml->createElement("instruction");
            $instruction->setAttribute("order", $counter);
            $instruction->setAttribute("opcode", $line[0]);
            $xml_program->appendChild($instruction);
            break;

        case 'DEFVAR':
        case 'POPS':
            if (count($line) != 2)
                exit(23);

            $instruction = $xml->createElement("instruction");
            $instruction->setAttribute("order", $counter);
            $instruction->setAttribute("opcode", $line[0]);
            $xml_program->appendChild($instruction);

            # arg 1 - var
            $arg_type = opcode_argument_type($line[1]);
            $line[1] = remove_chars($line[1]);
            if ($arg_type != 2)
                exit(23);

            $xml_argument = $xml->createElement('arg1', $line[1]);
            $xml_argument->setAttribute("type", "var");
            $instruction->appendChild($xml_argument);
            break;

        case 'CALL':
        case 'LABEL':
        case 'JUMP':
            if (count($line) != 2)
                exit(23);

            $instruction = $xml->createElement("instruction");
            $instruction->setAttribute("order", $counter);
            $instruction->setAttribute("opcode", $line[0]);
            $xml_program->appendChild($instruction);

            # arg 1 - label
            $arg_type = opcode_argument_type($line[1]);
            $line[1] = remove_chars($line[1]);
            if ($arg_type != 1)
                exit(23);

            $xml_argument = $xml->createElement('arg1', $line[1]);
            $xml_argument->setAttribute("type", "label");
            $instruction->appendChild($xml_argument);
            break;

        case 'PUSHS':
        case 'WRITE':
        case 'EXIT':
        case 'DPRINT':
            if (count($line) != 2)
                exit(23);
            $instruction = $xml->createElement("instruction");
            $instruction->setAttribute("order", $counter);
            $instruction->setAttribute("opcode", $line[0]);
            $xml_program->appendChild($instruction);

            # arg 1 - var / constant
            $arg_type = opcode_argument_type($line[1]);
            $line[1] = remove_chars($line[1]);
            if($arg_type == 2) {
                $xml_argument = $xml->createElement('arg1', $line[1]);
                $xml_argument->setAttribute("type", "var");
                $instruction->appendChild($xml_argument);
            } elseif ($arg_type == 3) {
                $explode = explode('@', $line[1]);
                $xml_argument = $xml->createElement('arg1', $explode[1]);
                $xml_argument->setAttribute("type", $explode[0]);
                $instruction->appendChild($xml_argument);
            } else
                exit(23);
            break;

        case 'INT2CHAR':
        case 'STRLEN':
            if (count($line) != 3)
                exit(23);

            $instruction = $xml->createElement("instruction");
            $instruction->setAttribute("order", $counter);
            $instruction->setAttribute("opcode", $line[0]);
            $xml_program->appendChild($instruction);

            # arg 1 - var
            $arg_type = opcode_argument_type($line[1]);
            $line[1] = remove_chars($line[1]);
            if ($arg_type != 2)
                exit(23);

            $xml_argument = $xml->createElement('arg1', $line[1]);
            $xml_argument->setAttribute("type", "var");
            $instruction->appendChild($xml_argument);

            # arg 2 - var / constant
            $arg_type = opcode_argument_type($line[2]);
            $line[2] = remove_chars($line[2]);
            if($arg_type == 2) {
                $xml_argument = $xml->createElement('arg2', $line[2]);
                $xml_argument->setAttribute("type", "var");
                $instruction->appendChild($xml_argument);
            } elseif ($arg_type == 3) {
                $explode = explode('@', $line[2]);
                $xml_argument = $xml->createElement('arg2', $explode[1]);
                $xml_argument->setAttribute("type", $explode[0]);
                $instruction->appendChild($xml_argument);
            } else
                exit(23);
            break;

        case 'MOVE':
        case 'TYPE':
        case 'NOT':
            if (count($line) != 3)
                exit(23);
            $instruction = $xml->createElement("instruction");
            $instruction->setAttribute("order", $counter);
            $instruction->setAttribute("opcode", $line[0]);
            $xml_program->appendChild($instruction);

            # arg 1 - var
            $arg_type = opcode_argument_type($line[1]);
            $line[1] = remove_chars($line[1]);
            if ($arg_type != 2)
                exit(23);

            $xml_argument = $xml->createElement('arg1', $line[1]);
            $xml_argument->setAttribute("type", "var");
            $instruction->appendChild($xml_argument);

            # arg 2 - var / constant
            $arg_type = opcode_argument_type($line[2]);
            $line[2] = remove_chars($line[2]);
            if($arg_type == 2) {
                $xml_argument = $xml->createElement('arg2', $line[2]);
                $xml_argument->setAttribute("type", "var");
                $instruction->appendChild($xml_argument);
            } elseif ($arg_type == 3) {
                $explode = explode('@', $line[2]);
                $xml_argument = $xml->createElement('arg2', $explode[1]);
                $xml_argument->setAttribute("type", $explode[0]);
                $instruction->appendChild($xml_argument);
            } else
                exit(23);
            break;

        case 'READ':
            if (count($line) != 3)
                exit(23);

            $instruction = $xml->createElement("instruction");
            $instruction->setAttribute("order", $counter);
            $instruction->setAttribute("opcode", $line[0]);
            $xml_program->appendChild($instruction);

            # arg 1 - var
            $arg_type = opcode_argument_type($line[1]);
            $line[1] = remove_chars($line[1]);
            if ($arg_type != 2)
                exit(23);

            $xml_argument = $xml->createElement('arg1', $line[1]);
            $xml_argument->setAttribute("type", "var");
            $instruction->appendChild($xml_argument);

            # arg 2 - type
            $arg_type = opcode_argument_type($line[2]);
            $line[2] = remove_chars($line[2]);
            if ($arg_type != 0)
                exit(23);

            $xml_argument = $xml->createElement('arg2', $line[2]);
            $xml_argument->setAttribute("type", "type");
            $instruction->appendChild($xml_argument);
            break;

        case 'AND':
        case 'OR':
        case 'ADD':
        case 'SUB':
        case 'MUL':
        case 'IDIV':
        case 'LT':
        case 'GT':
        case 'EQ':
        case 'STRI2INT':
        case 'GETCHAR':
        case 'CONCAT':
        case 'SETCHAR':
            if (count($line) != 4)
                exit(23);
            $instruction = $xml->createElement("instruction");
            $instruction->setAttribute("order", $counter);
            $instruction->setAttribute("opcode", $line[0]);
            $xml_program->appendChild($instruction);

            # arg 1 - var
            $arg_type = opcode_argument_type($line[1]);
            $line[1] = remove_chars($line[1]);
            if ($arg_type != 2)
                exit(23);

            $xml_argument = $xml->createElement('arg1', $line[1]);
            $xml_argument->setAttribute("type", "var");
            $instruction->appendChild($xml_argument);

            # arg 2 - var / constant
            $arg_type = opcode_argument_type($line[2]);
            $line[2] = remove_chars($line[2]);
            if($arg_type == 2) {
                $xml_argument = $xml->createElement('arg2', $line[2]);
                $xml_argument->setAttribute("type", "var");
                $instruction->appendChild($xml_argument);
            } elseif ($arg_type == 3) {
                $explode = explode('@', $line[2]);
                $xml_argument = $xml->createElement('arg2', $explode[1]);
                $xml_argument->setAttribute("type", $explode[0]);
                $instruction->appendChild($xml_argument);
            } else
                exit(23);

            # arg 3 - var / constant
            $arg_type = opcode_argument_type($line[3]);
            $line[3] = remove_chars($line[3]);
            if($arg_type == 2) {
                $xml_argument = $xml->createElement('arg3', $line[3]);
                $xml_argument->setAttribute("type", "var");
                $instruction->appendChild($xml_argument);
            } elseif ($arg_type == 3) {
                $explode = explode('@', $line[3]);
                $xml_argument = $xml->createElement('arg3', $explode[1]);
                $xml_argument->setAttribute("type", $explode[0]);
                $instruction->appendChild($xml_argument);
            } else
                exit(23);
            break;

        case 'JUMPIFEQ':
        case 'JUMPIFNEQ':
        if (count($line) != 4)
            exit(23);
        $instruction = $xml->createElement("instruction");
        $instruction->setAttribute("order", $counter);
        $instruction->setAttribute("opcode", $line[0]);
        $xml_program->appendChild($instruction);

        # arg 1 - label
        $arg_type = opcode_argument_type($line[1]);
        $line[1] = remove_chars($line[1]);
        if ($arg_type != 1)
            exit(23);

        $xml_argument = $xml->createElement('arg1', $line[1]);
        $xml_argument->setAttribute("type", "label");
        $instruction->appendChild($xml_argument);

        # arg 2 - var / constant
        $arg_type = opcode_argument_type($line[2]);
        $line[2] = remove_chars($line[2]);
        if($arg_type == 2) {
            $xml_argument = $xml->createElement('arg2', $line[2]);
            $xml_argument->setAttribute("type", "var");
            $instruction->appendChild($xml_argument);
        } elseif ($arg_type == 3) {
            $explode = explode('@', $line[2]);
            $xml_argument = $xml->createElement('arg2', $explode[1]);
            $xml_argument->setAttribute("type", $explode[0]);
            $instruction->appendChild($xml_argument);
        } else
            exit(23);

        # arg 3 - var / constant
        $arg_type = opcode_argument_type($line[3]);
        $line[3] = remove_chars($line[3]);
        if($arg_type == 2) {
            $xml_argument = $xml->createElement('arg3', $line[3]);
            $xml_argument->setAttribute("type", "var");
            $instruction->appendChild($xml_argument);
        } elseif ($arg_type == 3) {
            $explode = explode('@', $line[3]);
            $xml_argument = $xml->createElement('arg3', $explode[1]);
            $xml_argument->setAttribute("type", $explode[0]);
            $instruction->appendChild($xml_argument);
        } else
            exit(23);
        break;

        # don't increase order counter in case of an empty line
        case '':
            goto after_counter_inc;

        # ERROR in case of unrecognized instruction
        default:
            exit(22);
    }
    ++$counter;
    after_counter_inc:
}

echo $xml->saveXML(null, LIBXML_NOEMPTYTAG);
exit(0);

function opcode_argument_type($arg): int {
    # Function checks the type of argument
    # return codes:
    #     type: 0
    #    label: 1
    # variable: 2
    # constant: 3
    global $variable, $constant_no_str, $type, $label, $str;

    $exp = explode('@', $arg);
    if (count($exp) < 2) {
        if (preg_match($type, $arg))
            $arg_type = 0;
        elseif (preg_match($label, $arg)){
            $arg_type = 1;
        } else
            exit(23);
    }
    else {
        if (preg_match($variable, $arg)) {
            $arg_type = 2;
        } elseif (preg_match($constant_no_str, $arg)) {
            $explode = $exp[0]($exp[1]);
            if($explode == null)
                exit(23);
            $arg_type = 3;
        } elseif (preg_match($str, $arg)) {
            $arg_type = 3;
        } else
            exit(23);
    }
    return $arg_type;
}

# types:
# int, bool, string, nil, label, type, var

function int($int){
    # checks if int is a number
    return is_numeric($int) ? $int : null;
}

function bool($bool){
    # check if bool is written correctly
    if (strcmp("$bool", "true") == 0)
        return "true";
    if (strcmp("$bool", "false") == 0)
        return "false";
    return null;
}
//
//function string($string) {
//    # function needed for possibility to call functions with explode[0](explode[1])
//    return "true";
//}

function nil($nil){
    # checks if nil is written correctly
    return strcmp($nil, "nil") == 0 ? $nil: null;
}

function remove_chars($string){
    # Function that returns string with special characters replaced by their xml friendly equivalent
    $string = preg_replace("/&/", "&amp;", $string);
    $string = preg_replace("/</", "&lt;", $string);
    $string = preg_replace("/>/", "&gt;", $string);
    $string = preg_replace("/'/", "&#39;", $string);
    return preg_replace("/\"/", "&#34;", $string);
}