<?php

class Validator
{
    protected $messages;
    protected $has_errors;
    protected $error_list;

    function __construct($object = null, $rules_array = null)
    {
        $this->messages = $this->init_error_messages();

        if (isset($object) && isset($rules_array))
            $this->validate($object, $rules_array);
    }

    function validate($object, $rules_array, $only_validate_set = false)
    {
        if (is_array($object)) {
            foreach ($rules_array as $property_name => $property_rules) {
                if (($only_validate_set == true && isset($object[$property_name]) || ($only_validate_set == false)))
                    $this->validate_field($object[$property_name], $property_rules, $property_name);
            }
        } else {
            foreach ($rules_array as $property_name => $property_rules) {
                $this->validate_field($object->$property_name, $property_rules, $property_name);
            }
        }

    }

    function validate_field($field, $rules, $fieldName)
    {
        $rules = explode('|', $rules);

        foreach ($rules as $rule) {
            $result = null;

            // Get the parameter (if exists) from the rule
            $param = false;
            if (preg_match("/(.*?)\[(.*?)\]/", $rule, $match)) {
                $rule = $match[1];
                $param = $match[2];
            }

            // Call the function that corresponds to the rule
            if ($rule == 'clean') {
                $field = $this->clean($field);
            } else if (method_exists($this, $rule)) {
                $result = $this->$rule($field, $param);
            } else {
                trigger_error($rule . " is not a recognized validation rule", E_USER_WARNING);
            }

            // Did the rule fail?  If so, grab the error.
            if ($result === false) {
                $this->has_errors = true;
                //$this->error_list[$fieldName][$rule] = $this->messages[$rule];
                $this->error_list[$fieldName][] = $this->messages[$rule];
            }
        }
        return $field;
    }


    function set_error($field, $error)
    {
        $this->has_errors = true;
        $this->error_list[$field][] = $error;
    }

    function has_errors()
    {
        return $this->has_errors;
    }

    function errors()
    {
        return $this->error_list;
    }

    function required($str, $val = false)
    {
        if (!is_array($str)) {
            $str = trim($str);
            return ($str == '') ? false : true;
        } else {
            return (!empty($str));
        }
    }

    // --------------------------------------------------------------------


    function min_length($str, $val)
    {
        if (preg_match("/[^0-9]/", $val)) {
            return false;
        }

        return (strlen($str) < $val) ? false : true;
    }

    // --------------------------------------------------------------------


    function max_length($str, $val)
    {
        if (preg_match("/[^0-9]/", $val)) {
            return false;
        }

        return (strlen($str) > $val) ? false : true;
    }

    // --------------------------------------------------------------------


    function exact_length($str, $val)
    {
        if (preg_match("/[^0-9]/", $val)) {
            return false;
        }

        return (strlen($str) != $val) ? false : true;
    }

    // --------------------------------------------------------------------


    function valid_email($str)
    {
        //return (!preg_match('/^[^\W][a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\@[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\.[a-zA-Z]{2,4}$/', $str)) ? false : true;
        return (!preg_match("/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD", $str))
                ? false : true;
    }

    // --------------------------------------------------------------------


    function numeric($str)
    {
        return (!is_numeric($str)) ? false : true;
    }

    // --------------------------------------------------------------------


    //Validate the calendar date in MM/DD/YYYY format
    function valid_date($str)
    {
        return (!preg_match('#^(0?[1-9]|[12][0-9]|3[01])[- /.]((0?[1-9]|1[012])[- /.](19|20)?[0-9]{2})*$#', $str))
                ? false : true;
    }

    // --------------------------------------------------------------------


    //Validate all 2-letter US State abbreviattions
    function valid_state($str)
    {
        return (!preg_match('/^(?:A[KLRZ]|C[AOT]|D[CE]|FL|GA|HI|I[ADLN]|K[SY]|LA|M[ADEINOST]|N[CDEHJMVY]|O[HKR]|PA|RI|S[CD]|T[NX]|UT|V[AT]|W[AIVY])*$/i', $str))
                ? false : true;
    }

    // --------------------------------------------------------------------


    //Validate US ZIP Codes, with an optional 4 number ZIP code extension
    function valid_zip($str)
    {
        return (!preg_match('/^([0-9]{5}(?:-[0-9]{4})?)*$/', $str)) ? false : true;
    }

    // --------------------------------------------------------------------


    //Validate a 10-digit US phone number.
    //Separators are not required, but can include spaces, hyphens, or periods.
    function valid_phone($str)
    {
        return (!preg_match('/^(?:1(?:[. -])?)?(?:\((?=\d{3}\)))?([2-9]\d{2})(?:(?<=\(\d{3})\))? ?(?:(?<=\d{3})[.-])?([2-9]\d{2})[. -]?(\d{4})(?: (?i:ext)\.? ?(\d{1,5}))?$/', $str))
                ? false : true;
    }

    // --------------------------------------------------------------------


    function valid_url($str)
    {
        //Most restrictive, requires www and http
        //return (!preg_match('/^(http|https|ftp):\/\/([\w]*)\.([\w]*)\.(com|net|org|biz|info|mobi|us|cc|bz|tv|ws|name|co|me)(\.[a-z]{1,3})?\z/i', $str)) ? false : true;

        //Less restrictive, doesn't require www, but requires http
        //return (!preg_match("/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i", $str)) ? false : true;


        if (!preg_match("/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i", $str)) {
            if (!preg_match("/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i", 'http://' . $str)) {
                return false;
            } else return true;
        } else return true;
    }

    // --------------------------------------------------------------------


    //Passwords must be at least 6 characters long with one uppercase letter and one number
    function strong_password($str)
    {
        return (!preg_match('/^(?=^.{6,}$)((?=.*[A-Za-z0-9])(?=.*[A-Z])(?=.*[a-z]))^.*$/', $str)) ? false : true;
    }

    // --------------------------------------------------------------------


    function alpha($str)
    {
        return (!preg_match("/^([a-z])+$/i", $str)) ? false : true;
    }

    // --------------------------------------------------------------------


    function min($str, $val)
    {
        return ($str >= $val) ? true : false;
    }

    // --------------------------------------------------------------------


    function max($str, $val)
    {
        return ($str <= $val) ? true : false;
    }

    // --------------------------------------------------------------------


    function alpha_numeric($str)
    {
        return (!preg_match("/^([a-z0-9])+$/i", $str)) ? false : true;
    }

    // --------------------------------------------------------------------


    function matches($str, $field)
    {
        return ($str !== $field) ? false : true;
    }

    // --------------------------------------------------------------------


    function clean($str)
    {
        $str = is_array($str) ? array_map('Form::clean', $str)
                : str_replace('\\', '\\\\', strip_tags(trim(htmlspecialchars((get_magic_quotes_gpc()
                        ? stripslashes($str) : $str), ENT_QUOTES))));

        return $str;
    }

    // --------------------------------------------------------------------


    function init_error_messages()
    {
        $error_messages = array();

        $error_messages['required'] = 'This field is required';
        $error_messages['min_length'] = 'This field must meet the minimum length';
        $error_messages['max_length'] = 'This field must meet the maximum length';
        $error_messages['exact_length'] = 'This field must be a valid length';
        $error_messages['valid_email'] = 'Please enter a valid email address';
        $error_messages['valid_url'] = 'Please enter a valid url';
        $error_messages['numeric'] = 'Please enter a valid numeric value';
        $error_messages['alpha'] = 'Please enter a valid alpha value';
        $error_messages['alpha_numeric'] = 'Please enter a valid alpha numeric value';
        $error_messages['valid_date'] = 'Please enter a valid date in the format mm/dd/yyyy';
        $error_messages['valid_state'] = 'Please enter a valid US state';
        $error_messages['valid_zip'] = 'Please enter a valid US zip code';
        $error_messages['valid_phone'] = 'Please enter a valid 10 digit phone number';
        $error_messages['matches'] = 'The values do not match';
        $error_messages['min'] = 'Please enter a value within the allowed range';
        $error_messages['max'] = 'Please enter a value within the allowed range';
        $error_messages['strong_password'] = 'Please enter a password that contains at least one uppercase letter, one lowercase letter, and a number';

        return $error_messages;
    }


}

?>