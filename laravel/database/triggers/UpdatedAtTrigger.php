<?php

namespace Database\Triggers;

class UpdatedAtTrigger
{
    public static function create(string $table): string
    {
        return "
            DROP TRIGGER IF EXISTS set_updated_at ON $table;
            CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON $table
            FOR EACH ROW
            EXECUTE PROCEDURE update_updated_at_column();
        ";
    }

    public static function drop(string $table): string
    {
        return "DROP TRIGGER IF EXISTS set_updated_at ON $table;";
    }
}
