<?php
class Config
{

    public static string $env = 'local'; //change to server when deploy
    private static array $db_local = [
        "host" => "db",
        "port" => 5432,
        "database" => "hw3_db",
        "user" => "localuser",
        "password" => "cs4640LocalUser!"
    ];

    private static $db_server = [
        'gsm3cx' => [
            "host" => "localhost",
            "port" => "5432",
            "database" => "gsm3cx",
            "user" => "gsm3cx",
            "password" => "	mEt-Cc_dJXgU",
        ],
        'kus8en' => [
            "host" => "localhost",
            "port" => "5432",
            "database" => "kus8en",
            "user" => "kus8en",
            "password" => "PASSWORD_FROM_CANVAS_FOR_abc1de",
        ],
    ];


    public static function connect()
    {
        $env = getenv('APP_ENV') ?: self::$env;

        if ($env === 'server') {
            $id = getenv('COMPUTING_ID');
            if (!$id || !isset(self::$db_server[$id])) {
                die("Unknown or missing COMPUTING_ID environment variable");
            }
            $db = self::$db_server[$id];
        } else {
            $db = self::$db_local;
        }

        $connectionString = sprintf(
            "host=%s port=%d dbname=%s user=%s password=%s",
            $db['host'],
            $db['port'],
            $db['database'],
            $db['user'],
            $db['password']
        );

        $connection = pg_connect($connectionString);
        if (!$connection) {
            die("Database connection failed");
        }

        return $connection;

    }


}
;