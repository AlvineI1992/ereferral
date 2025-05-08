<?php

// app/Enums/Permissions.php

namespace App\Enums;

class Permissions
{

    public static function dashboard(): array
    {
        return [
            ['dashboard_referral monthly'],
            ['dashboard_referral typeofservice'],
            ['dashboard_referral reasonofreferral']
        ];
    }

    public static function demographic(): array
    {
        return [
            ['demographic create'],
            ['demographic list'],
            ['demographic edit'],
            ['demographic delete'],
            ['demographic print']
        ];
    }

    public static function appointment_data(): array
    {
        return [
            ['appointment_data create'],
            ['appointment_data list'],
            ['appointment_data edit'],
            ['appointment_data delete'],
          
        ];
    }

    public static function appointment(): array
    {
        return [
            ['appointment create'],
            ['appointment list'],
            ['appointment edit'],
            ['appointment delete'],
            ['appointment print']
        ];
    }

    public static function beds(): array
    {
        return [
            ['beds create'],
            ['beds list'],
            ['beds edit'],
            ['beds delete'],
            ['beds print']
        ];
    }

    public static function records_data(): array
    {
        return [
            ['records_data create'],
            ['records_data list'],
            ['records_data edit'],
            ['records_data delete'],
          
        ];
    }

    public static function records(): array
    {
        return [
            ['records create'],
            ['records list'],
            ['records edit'],
            ['records delete'],
            ['records print']
        ];
    }

    public static function patient_data(): array
    {
        return [
            ['patient_data create'],
            ['patient_data list'],
            ['patient_data edit'],
            ['patient_data delete'],
          
        ];
    }

    public static function patient(): array
    {
        return [
            ['patient create'],
            ['patient list'],
            ['patient edit'],
            ['patient delete'],
            ['patient print']
        ];
    }

    public static function outgoing_data(): array
    {
        return [
            ['outgoing_data create'],
            ['outgoing_data list'],
            ['outgoing_data edit'],
            ['outgoing_data delete'],
          
        ];
    }

    public static function outgoing(): array
    {
        return [
            ['outgoing create'],
            ['outgoing list'],
            ['outgoing edit'],
            ['outgoing delete'],
            ['outgoing print']
        ];
    }

    public static function incoming_data(): array
    {
        return [
            ['incoming_data create'],
            ['incoming_data list'],
            ['incoming_data edit'],
            ['incoming_data delete'],
        ];
    }

    public static function incoming(): array
    {
        return [
            ['incoming create'],
            ['incoming list'],
            ['incoming edit'],
            ['incoming delete'],
            ['incoming print']
        ];
    }

    public static function user(): array
    {
        return [
            ['user create'],
            ['user list'],
            ['user edit'],
            ['user delete'],
            ['user assign'],
        ];
    }

    public static function facility(): array
    {
        return [
            ['facility create'],
            ['facility list'],
            ['facility edit'],
            ['facility delete'],
        ];
    }

    public static function provider(): array
    {
        return [
            ['provider create'],
            ['provider list'],
            ['provider edit'],
            ['provider delete'],
            ['provider assign'],
        ];
    }

    public static function roles(): array
    {
        return [
            ['role create'],
            ['role list'],
            ['role edit'],
            ['role delete'],
            ['role assign'],
        ];
    }

    public static function permission(): array
    {
        return [
            ['permission create'],
            ['permission list'],
            ['permission edit'],
            ['permission delete'],
        ];
    }
}
