<?php

namespace App\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\MySqlGrammar;

class LegacyMySqlGrammar extends MySqlGrammar
{
    /**
     * Compile column discovery without generation_expression, which is absent
     * from older MySQL/MariaDB information_schema implementations.
     */
    public function compileColumns($schema, $table)
    {
        return sprintf(
            'select column_name as `name`, data_type as `type_name`, column_type as `type`, '
            .'collation_name as `collation`, is_nullable as `nullable`, '
            .'column_default as `default`, column_comment as `comment`, '
            .'null as `expression`, extra as `extra` '
            .'from information_schema.columns where table_schema = %s and table_name = %s '
            .'order by ordinal_position asc',
            $schema ? $this->quoteString($schema) : 'schema()',
            $this->quoteString($table)
        );
    }
}
