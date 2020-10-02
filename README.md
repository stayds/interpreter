# Interpreter

[![N|Mtech](https://betconverter.com/logo_main.png)](https://betconverter.com/)

[![Build Status](https://travis-ci.org/joemccann/dillinger.svg?branch=master)](https://betconverter.com/)

> Interpreter converts betcode to actual booked games, outcomes and bet types.

# class Structure

  - entrypoint.php 
  - Interpreter.php
  - BookmakerInterface
  - Bookmaker classes that implements the bookermakerinterface
  
# API Call

| Description | Endpoint URL |
| ------ | ------ |
| API | http://localhost/interpreter/entrypoint.php|

>You post the sample json request below to the above API endpoint

Sample json post request:
```sh
{
    "code": "BC24MK59",
    "homebookmaker": "Sportybet",
    "awaybookmaker": "Betking"
}
```

# On-going New Features!

  - Bet Odd comparison
  - Bet Outcome standardization