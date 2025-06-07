<?php

namespace App;

enum TrancationType:string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Sell = 'sell';
    case Buy = 'buy';
    case Return = 'return'; 
}
