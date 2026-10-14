<?php

namespace App\Enums;

enum InquiryType: string
{
    /** The Contact Us form. */
    case Contact = 'contact';

    /** The inquiry form on a property's details page. */
    case Property = 'property';
}
