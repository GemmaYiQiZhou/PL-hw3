<?php
class Config
{
    public static $env = 'local';
    private static $db_local = [
        "host" => "localhost",
        "port" => 5432,
        "database" => "hw3_users",
        "user" => "localuser",
        "password" => "cs4640LocalUser!"
    ];

    private static $db_server = [
        'gsm3cx' => [
            "host" => "localhost",
            "port" => "5432",
            "database" => "gsm3cx",
            "user" => "gsm3cx",
            "password" => "PASSWORD_FROM_CANVAS_FOR_abc1de",
        ],
        'kus8en' => [
            "host" => "localhost",
            "port" => "5432",
            "database" => "kus8en",
            "user" => "kus8en",
            "password" => "PASSWORD_FROM_CANVAS_FOR_abc1de",
        ],
    ];


    public static function db(): array
    {
        if (self::$env === 'server') {
            $id = getenv('COMPUTING_ID') ?: 'gsm3cx';
            if (!isset(self::$db_server[$id])) {
                throw new RuntimeException("Unknown COMPUTING_ID '$id'");
            }
            return self::$db_server[$id];
        }

        return self::$db_local;

    }
}
;



$dbHandle = pg_connect("host=$host port=$port dbname=$database user=$user password=$password");

if ($dbHandle) {
    echo "Success connecting to database";
} else {
    echo "An error occurred connecting to the database";
}
