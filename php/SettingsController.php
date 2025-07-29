<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Types\TableName;
use App\Types\EnumList;
use Illuminate\Support\Arr;
use ReflectionClass;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\View;

use function PHPUnit\Framework\isEmpty;

class SettingsController extends Controller
{
    protected $config;
    protected $path;
    protected $contents;
    private $opCode = [
        'soft-delete' => 'softDelete',
        'hard-delete' => 'hardDelete',
        'update' => 'updateValue',
        'rollback' => 'rollback',
    ];
    protected array $classCache = [];

    protected $illuminateClasses;

    public function __construct()
    {
        if (env('APP_ENV') === 'production' || env('APP=DEBUG') === 'false') {
            abort(403);
        }

        $this->path = base_path('.env');
        $this->contents = File::get($this->path);

        $lines = explode("\n", trim($this->contents));
        $config = [];

        foreach ($lines as $line) {
            if (!isEmpty($line) || str_contains($line, '=')) {
                list($key, $value) = explode("=", $line, 2);
                $config[trim($key)] = trim($value);
            }
        }

        $this->config = $config;
    }

    public function edit(Request $request)
    {
        $table_name = $request->query('table_name');

        $AorB = [
            "before" => "before",
            "after"  => "after"
        ];

        return view('settings', ['config' => $this->config, 'table_name' => $table_name, 'AorB' => $AorB, 'table_list' => TableName::toArray(true)]);
    }


    private function sessionStore(Request $request): void
    {
        $input = $request->all();
        session()->put($input);
    }

    private function genResult(bool $status, string $message): array
    {
        return [
            'result'  => $status,
            'message' => $message,
            'type'    => $status ? 'message.info' : 'message.error',
        ];
    }

    private function handleWithResult(callable $callback, bool $default = true, string $url = null)
    {
        try {
            $message = $callback();

            return $this->genResult(true, $message);
        } catch (\Exception $e) {
            $result = $this->genResult(false, 'Error occurred: ' . $e->getMessage());

            if ($default) {
                return $result;
            }

            return redirect($url ?? '/settings')->with($result['type'], $result['message']);
        }
    }

    // ENV file
    public function update(Request $request)
    {
        $this->sessionStore($request);

        $input = $request->all();

        $this->updateEnvFile($input);

        return redirect('/settings')->with('message.info', 'Settings updated successfully!');
    }

    protected function updateEnvFile($input)
    {
        foreach ($input as $key => $value) {
            if ($key == '_token') {
                continue;
            }
            if (preg_match("/^{$key}=.*/m", $this->contents)) {
                $this->contents = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $this->contents);
            } else {
                $this->contents .= "\n{$key}={$value}";
            }
        }

        File::put($this->path, $this->contents);
    }


    //
    //
    // DDDDDDDDDD         BBBBBBBBBB        //
    // D         D        B         B       //
    // D          D       B          B      //
    // D          D       B         B       //
    // D          D       BBBBBBBBBB        //
    // D          D       B         B       //
    // D          D       B          B      //
    // D          D       B          B      //
    // D         D        B         B       //
    // DDDDDDDDDD         BBBBBBBBBB        //
    //
    //
    public function editdb(Request $request)
    {
        $table_name = $request->query('table_name');

        $AorB = [
            "before" => "before",
            "after"  => "after"
        ];

        return view('settings', compact('table_name', 'AorB'));
    }

    public function updateDB(Request $request)
    {
        $this->sessionStore($request);

        $input = $request->input();

        if (!$input['table_name']) {
            return redirect('/settings')->with('message.error', 'Input Nothing');
        }

        // get table name
        $result = $this->handleWithResult(function () use ($input) {
            $table_name = $this->tableNameInputHandling($input['table_name']);

            list($before, $after) = explode(';', $input['operate']);

            $this->up($table_name, $before, $after);

            return "Update OK: TABLE $table_name FROM $before TO $after";
        });

        return redirect('/settings?table_name=' . urlencode($input['table_name']))
            ->with($result['type'], $result['message']);
    }

    private function up($table, $before, $after)
    {
        if ($before == 'table_names') {
            $before = $table;
            $after = substr($table, 0, -1);

            Schema::rename($before, $after);
            return;
        }

        if ($before == 'table_name') {
            $after = $table;
            $before = substr($table, 0, -1);

            Schema::rename($before, $after);
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($before, $after) {
            $table->renameColumn($before, $after);
            return;
        });

        return;
    }

    private function tableNameInputHandling($originTableName)
    {
        if ($this->getTableName($originTableName)) {
            return $this->getTableName($originTableName);
        }

        $patterns = [
            "/（(.*?)テーブル）/",
            "/：(.*?)テーブル/",
            "/：(.*)/",
            "/(.*?)テーブル/",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $originTableName, $matches)) {
                $tableName = $matches[1] ?? '';
                $tableNameEN = $this->getTableName($tableName);
                if ($tableNameEN) {
                    return $tableNameEN;
                }
            }
        }

        throw new \Exception('Incorrect table name: ' . $originTableName);
    }

    private function getTableName($tableNameJP)
    {
        // if $tableNameJP is english name of table
        // return itself
        foreach (TableName::cases() as $case) {
            if ($case->key() === $tableNameJP) {
                return $tableNameJP;
            }
        }

        // or search the real name from JP name
        $key = array_search($tableNameJP, TableName::toArray());
        if ($key) {
            return TableName::convert($key)->key();
        }

        return false;
    }

    //
    //
    // DDDDD     U     U   M     M   PPPPP     //
    // D    D    U     U   MM   MM   P    P    //
    // D     D   U     U   M M M M   P    P    //
    // D     D   U     U   M  M  M   PPPPP     //
    // D     D   U     U   M     M   P         //
    // D    D    U     U   M     M   P         //
    // DDDDD      UUUUU    M     M   P         //
    //
    //
    public function dump(Request $request)
    {
        $this->sessionStore($request);

        $input = $request->input();

        $name1 = $input['case'] ? $input['case'] : 'database';

        if ($input['case']) {
            $name2 = isset($input['before-or-after']) ? (
                $input['before-or-after'] == 'before' ? '_実施前' : ($input['before-or-after'] == 'after' ? '_実施後' : '')
            ) : '';
        } else {
            $name2 = '';
        }

        $result = $this->dumpDatabase($name1 . $name2);

        return redirect('/settings?case=' . urlencode($name1))->with($result['type'], $result['message']);
    }

    private function dumpDatabase($filename)
    {
        $cmd = 'mysqldump --single-transaction -ucare -pcare -hmysql care_smart_db > ../' . $filename . '.sql';

        return $this->handleWithResult(function () use ($cmd, $filename) {
            $process = new Process([
                'bash',
                '-c',
                $cmd
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput());
            }

            return 'Operate succeed: Dump file created as ' . $filename . '.sql';
        });
    }

    //
    //
    // RRRR     EEEEE   FFFFF  RRRR    EEEEE  SSSS   DDDDD    BBBB   //
    // R   R    E       F      R   R   E     S       D    D   B   B  //
    // RRRR     EEEE    FFFF   RRRR    EEEE   SSSS   D    D   BBBB   //
    // R R      E       F      R R     E          S  D    D   B   B  //
    // R  RR    EEEEE   F      R  RR   EEEEE  SSSS   DDDDD    BBBB   //
    //
    //
    public function refreshDB(Request $request)
    {
        $this->sessionStore($request);

        $result = $this->handleWithResult(function () {
            DB::statement('DROP DATABASE IF EXISTS care_smart_db');
            DB::statement('CREATE DATABASE IF NOT EXISTS care_smart_db');

            return 'DB dropped and then created! Now you need migration.';
        });

        return redirect('/settings')->with($result['type'], $result['message']);
    }

    public function migration()
    {
        $result = $this->handleWithResult(function () {
            Artisan::call('migrate:refresh', ['--seed' => true]);
            Artisan::call('db:seed', ['--class' => 'DevelopmentSeeder']);

            return 'Migration succeeded!';
        });

        return redirect('/settings')->with($result['type'], $result['message']);
    }

    // change Column////////////////////////////////////////////
    public function editCol(Request $request)
    {
        $input = $request->input('table_list');

        $table = TableName::convert($input)->key();

        $columns = Schema::getColumnListing($table);

        $values = DB::table($table)->pluck($columns[0]);

        return response()->json([
            'columns' => $columns,
            'values' => $values,
        ]);
    }

    public function editValue(Request $request)
    {
        $column = $request->input('column');
        $tableNo = $request->input('table_list');
        $table = TableName::convert($tableNo)->key();

        $values = DB::table($table)->pluck($column);

        return response()->json(['values' => $values]);
    }


    // physics and logic delete////////////////////////////////////////////DB::rollBack();
    // physics and logic delete////////////////////////////////////////////DB::rollBack();
    // physics and logic delete////////////////////////////////////////////DB::rollBack();
    // physics and logic delete////////////////////////////////////////////DB::rollBack();
    // physics and logic delete////////////////////////////////////////////DB::rollBack();
    // physics and logic delete////////////////////////////////////////////DB::rollBack();
    // physics and logic delete////////////////////////////////////////////DB::rollBack();
    // physics and logic delete////////////////////////////////////////////DB::rollBack();
    // physics and logic delete////////////////////////////////////////////DB::rollBack();
    public function tableColumn(Request $request)
    {
        $which = $request->input();

        $validOperations = array_intersect_key($this->opCode, $which);

        $operation = key($validOperations);

        session(['table_list' => $which['table_list']]);

        if ($operation !== 'rollback') {
            $this->dumpDatabase('backup');

            $origin = base_path('backup.sql');
            $destination = storage_path('logs/backup.sql');
            File::move($origin, $destination);
        }

        $result = $this->{$this->opCode[$operation]}($which);

        return redirect('/settings')->with($result['type'], $result['message']);
    }

    private function softDelete($do)
    {
        $table = $do['table_list'];
        $value = $do['this_value'];
        $column = $do['column'];

        $table = TableName::convert($table)->key();

        return $this->handleWithResult(function () use ($table, $column, $value) {
            DB::table($table)
                ->where($column, "=", $value)
                ->update([
                    'deleted_at' => now(),
                    'deleted_by' => 1
                ]);

            session(['lastOP' => [
                'op'    => 'soft-delete',
            ]]);

            return 'Operate succeed: Record ' . $value . ' has been soft-deleted';
        });
    }

    private function hardDelete($do)
    {
        $table = TableName::convert($do['table_list'])->key();
        $value = $do['this_value'];
        $column = $do['column'];

        $database = env('DB_DATABASE');

        $includeForeignTables = DB::select("
            SELECT TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND REFERENCED_TABLE_NAME = ?
        ", [$database, $table]);

        $foreignTables = array_map(fn($t) => $t->TABLE_NAME, $includeForeignTables);

        return $this->handleWithResult(function () use ($foreignTables, $table, $column, $value) {
            $this->dropForeignKey($foreignTables);

            DB::table($table)->where($column, $value)->delete();

            session(['lastOP' => [
                'op' => 'hard-delete'
            ]]);

            return  "Record {$value} has been hard-deleted.";
        });
    }

    private function dropForeignKey($tableArr)
    {
        $database = env('DB_DATABASE');

        foreach ($tableArr as $table) {
            $foreignKeys = DB::select("
                SELECT
                    CONSTRAINT_NAME
                FROM
                    information_schema.KEY_COLUMN_USAGE
                WHERE
                    TABLE_SCHEMA = ?
                    AND TABLE_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database, $table]);

            foreach ($foreignKeys as $foreignKey) {
                DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$foreignKey->CONSTRAINT_NAME`");
            }
        }
    }

    private function updateValue($do)
    {
        $table = $do['table_list'];
        $column = $do['column'];
        $value = $do['this_value'];
        $to_value = $do['to_value'];

        $table = TableName::convert($table)->key();

        return $this->handleWithResult(function () use ($table, $column, $value, $to_value) {
            DB::table($table)->where($column, $value)->update([$column => $to_value]);

            session(['lastOP' => [
                'op' => 'update'
            ]]);

            return "Operate succeed: Record with {$column} = {$value} has been updated to {$to_value}";
        });
    }

    private function rollback($do)
    {
        $path = storage_path('logs/backup.sql');

        $cmd = "mysql -usd -psd -hmysql csd < $path";

        return $this->handleWithResult(function () use ($cmd) {
            $process = new Process([
                'bash',
                '-c',
                $cmd
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput());
            }

            session()->forget('lastOP');

            return 'Operate succeed: Rollback';
        });
    }

    //
    // ER
    //
    public function get_fk(Request $request)
    {
        $tables = $request->input('tables');

        $table_name = $this->get_each_fr_input($tables);

        $ENtable_name = [];

        foreach ($table_name as $table_key) {
            if ($this->getTableName($table_key)) {
                $ENtable_name[] = $this->getTableName($table_key);
            } else {
                $ENtable_name[] = TableName::convert($table_key)->key();
            }
        }

        [$result, $fkData] = $this->get_fk_info($ENtable_name);

        session(['data' => $result, 'fkData' => $fkData]);

        return view('showER');
    }

    private function get_each_fr_input(mixed $inputs): array
    {
        if (is_string($inputs)) {
            $inputs = explode(" ", $inputs);
        }

        $results = [];
        foreach ($inputs as $input) {
            if (is_string($input)) {
                $input = trim(preg_replace('/\s+/', ' ', $input));
                $temp = array_filter(explode(" ", $input));
                $results = array_merge($results, $temp);
            } elseif (is_array($input)) {
                $results = array_merge($results, $this->get_each_fr_input($input));
            }
        }

        return $results;
    }

    private function get_fk_info(array $tables)
    {
        // Initialize an array to store the final results
        $results = [];

        // Initialize an array to store foreign key relationships
        $fks = [];

        $top = 300;
        $left = 800;

        // Loop through each table to retrieve foreign key and column information
        foreach ($tables as $table) {
            $fk_info = $this->get_fk_info_in_tbs($table, $tables);

            $columns = $this->get_cols($table);

            // Initialize an array to store column details
            $columns_array = [];
            foreach ($columns as $column) {
                $columns_array[$column->COLUMN_NAME] = [
                    "name" => $column->COLUMN_NAME,
                    "jpName" => !empty($column->COLUMN_COMMENT) ? $column->COLUMN_COMMENT : $column->COLUMN_NAME,
                    "show" => false,
                    // Indicates whether the column should be displayed
                    "is_fk" => false,
                ];
            }

            // Initialize an array to store foreign key data for the current table
            $fk_data = [];

            // Loop through the foreign key information and format it
            foreach ($fk_info as $row) {
                $fk_data[] = [
                    "child" => $row->child_table,
                    "childCol" => $row->child_col,
                    "father" => $row->father_table,
                    "fatherCol" => $row->father_col
                ];

                // Add the foreign key relationship to the $fks array
                $fks[] = [
                    "child" => $row->child_table,
                    "childCol" => $row->child_col,
                    "father" => $row->father_table,
                    "fatherCol" => $row->father_col
                ];
            }

            // Store the table's column and foreign key information in the results array
            $results[$table] = [
                "table" => $table,
                "jpTable" => TableName::getTextFromKey($table),
                "columns" => $columns_array,
                "fk" => $fk_data,
                "parent" => [],
                "pos" => [
                    "left" => $left,
                    "top"  => $top,
                ],
            ];

            $left += 20;
        }

        // Update the results array to mark columns with foreign keys
        foreach ($fks as $fk) {
            $child = $fk["child"];
            $childCol = $fk["childCol"];
            $father = $fk["father"];
            $fatherCol = $fk["fatherCol"];

            // Mark the columns involved in foreign key relationships
            Arr::set($results, "$child.columns.$childCol.show", true);
            Arr::set($results, "$father.columns.$fatherCol.show", true);
            Arr::set($results, "$child.columns.$childCol.is_fk", true);
            Arr::set($results, "$father.columns.$fatherCol.is_fk", true);

            if ($child <> $father) {
                $results[$child]["parent"][] = $father;
            }
        }

        // Return the final results array
        return [$results, $fks];
    }

    private function get_fk_info_in_tbs($tb, $tbs, $reverse = false)
    {
        $cond = $reverse ? ['TABLE_NAME', 'REFERENCED_TABLE_NAME'] : ['REFERENCED_TABLE_NAME', 'TABLE_NAME'];

        $sql = "SELECT
        TABLE_NAME as child_table,
        COLUMN_NAME as child_col,
        REFERENCED_TABLE_NAME as father_table,
        REFERENCED_COLUMN_NAME as father_col
    FROM
        information_schema.KEY_COLUMN_USAGE
    WHERE
        REFERENCED_TABLE_NAME IS NOT NULL
        AND TABLE_SCHEMA = ?
        AND {$cond[0]} = ?
        AND {$cond[1]} IN (" . implode(',', array_fill(0, count($tbs), '?')) . ")
";

        return DB::select(
            $sql,
            array_merge(['care_smart_db', $tb], $tbs)
        );
    }

    private function get_cols($tb)
    {
        return DB::select(
            "SELECT COLUMN_NAME, COLUMN_COMMENT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND COLUMN_NAME NOT IN (?, ?, ?, ?, ?, ?, ?)
            ORDER BY ORDINAL_POSITION",
            [
                'care_smart_db',
                $tb,
                'created_at',
                'created_by',
                'updated_at',
                'updated_by',
                'deleted_at',
                'deleted_by',
                'version',
            ]
        );
    }

    public function fkIncrementalUpdate(Request $request)
    {
        [$past_table_infos, $fkData] = [session('data'), session('fkData')];

        $tables = $this->get_each_fr_input($request->input('tables'));

        $past_tables = array_keys($past_table_infos);

        $ENtable_name = [];

        foreach ($tables as $table_key) {
            if ($this->getTableName($table_key)) {
                $ENtable_name[] = $this->getTableName($table_key);
            } else {
                $ENtable_name[] = TableName::convert($table_key)->key();
            }
        }

        $all_tables = array_merge($past_tables, $ENtable_name);

        $new_fk_Info = [];
        foreach ($ENtable_name as $table) {
            $temp1 = $this->get_fk_info_in_tbs($table, $all_tables, true);
            $temp2 = $this->get_fk_info_in_tbs($table, $all_tables, false);

            $new_fk_Info = array_merge($new_fk_Info, $temp1, $temp2);
        }

        $a = 1;
    }

    // enum show
    public function getEnum(Request $request)
    {
        $enums = $request->input('enums');

        $enum_names = $this->get_each_fr_input($enums);

        if (count($enum_names) == 1) {
            return $this->getOneEnum($enum_names[0]);
        }

        $enumList = [];

        foreach (EnumList::cases() as $entity) {
            $enumList[$entity->key()] = $entity->text();
        }

        if (empty($enum_names)) {
            $data = $this->showAllEnum($enumList);
        } else {
            $data = $this->showEnums($enum_names, $enumList);
        }

        $enumData = [
            "head" => [
                "コード名称",
                "コード英名"
            ],
            "body" => $data,
        ];

        return view('showenum', ['data' => $enumData]);
    }

    private function showAllEnum($enumList)
    {
        $data = [];

        $enumFiles = File::files(app_path('Types'));

        foreach ($enumFiles as $enumFile) {
            $enumClassName = basename($enumFile->getPathname(), '.php');

            if (array_key_exists($enumClassName, $enumList)) {
                $data["$enumClassName"] = [
                    'key' => $enumClassName,
                    'text' => EnumList::getTextFromKey($enumClassName),
                    'url' => "http://admin.local/showenum/{$enumClassName}",
                ];
            }
        }

        return $data;
    }

    private function showEnums($enum_names, $enumList)
    {
        $data = $this->showAllEnum($enumList);

        $enums = [];

        foreach ($enum_names as $enum_name) {
            foreach ($data as $key => $enum) {
                if ($enum_name == $enum['key'] || $enum_name == $enum['text']) {
                    $enums[$key] = $enum;
                }
            }
        }


        return $enums;
    }

    public function getOneEnum($enum)
    {
        $ENUM = null;
        foreach (EnumList::cases() as $entity) {
            if ($entity->key() == $enum || $entity->text() == $enum) {
                $ENUM = $entity;
                break;
            }
        }

        $result = $this->handleWithResult(function () use ($ENUM, $enum) {
            if (!$ENUM) {
                throw new \Exception('Incorrect enum name: ' . $enum);
            }
            return 'ok';
        }, false);

        $enum_name = $ENUM->key();

        $enum_class = "App\\Types\\{$enum_name}";

        $enum_reflection = new \ReflectionEnum($enum_class);

        $enum_datas = [];

        foreach ($enum_reflection->getCases() as $case) {
            /** @var mixed $value */
            $value = $case->getValue();

            $enum_datas[] = [
                'value' => $value->value,
                'en_name_camel' => $case->name,
                'en_name_snake' => $value->key(),
                'jp_name' => $value->text(),
            ];
        }

        $data = [
            "head" => [
                "コード値",
                "英名",
                "テキスト",
                "名称"
            ],
            "body" => $enum_datas,
        ];

        return view('showenum', ['data' => $data]);
    }

    public function reflect(...$params)
    {
        $testClass = app()->make(Blueprint::class, ...$params);
        $test = new ReflectionClass($testClass);
        $t1 = $test->getMethods();
        $t3 = $test->getProperties();

        foreach ($t1 as $key => $value) {
            echo "<p><span>{$key}</span>: {$value->name}</p>";
        }

        dd($t1, $t3);
        $a = 1;
    }

    public function invokeReflect(Request $request)
    {
        $input = $request->input('class');

        $this->genClassMap();

        $className = 'Illuminate\\' . $this->illuminateClasses[$input];

        $class = new ReflectionClass($className);

        $methods = $class->getMethods();
        $properties = $class->getProperties();

        echo "<head><style>.para{size:8px;}.meth{size:16px}div:nth-child(2n){background-color:#eee}h4,hr{margin:0}</style></head>";
        echo "<h2>{$className}</h2>";
        foreach ($methods as $method) {
            echo " <div><h4 class=\"meth\">Method: {$method->getName()}</h4>";
            echo "<p class=\"para\">Parameters: [";
            $params = $method->getParameters();
            foreach ($params as $param) {
                echo "{$param->getName()}";
                if ($param->hasType()) {
                    echo " (" . $param->getType() . ")";
                }
                if ($param->isDefaultValueAvailable()) {
                    echo " = " . json_encode($param->getDefaultValue());
                }
                echo ", ";
            }
            echo "]</p>";
            echo "<p class=para>" . nl2br($method->getDocComment()) . "</p>";
            echo "<hr></div>";
        }

        foreach ($properties as $key => $property) {
            echo "<h4>Property {$key}: {$property->getName()}</h4>";
            echo "<p>Visibility: " .
                ($property->isPublic() ? 'public' : ($property->isProtected() ? 'protected' : 'private')) .
                "</p>";
            echo "<p>Static: " . ($property->isStatic() ? 'Yes' : 'No') . "</p>";
            echo "<hr>";
        }
        return;
    }

    public function getClasses()
    {
        return response()->json($this->genClassMap());
    }

    private function genClassMap()
    {
        $classMap = require base_path('vendor/composer/autoload_classmap.php');

        $illuminateClasses = array_values(array_filter(array_map(function ($class) {
            return str_starts_with($class, 'Illuminate\\') ?
                str_replace('Illuminate\\', '', $class) : null;
        }, array_keys($classMap)), function ($class) {
            return !empty($class);
        }));

        $this->illuminateClasses = $illuminateClasses;

        return $this->transformArray($illuminateClasses);
    }

    private function transformArray(array $input): array
    {
        $result = [];
        $i = 0;

        foreach ($input as $item) {
            $segments = explode('\\', $item);
            $ref = &$result;

            foreach ($segments as $segment) {
                if (!isset($ref[$segment])) {
                    $ref[$segment] = [];
                }
                $ref = &$ref[$segment];
            }

            $ref = $i++;
        }

        return $result;
    }

    public function showTables(Request $request)
    {
        $input = $request->input('showTables');

        $table_name = $this->get_each_fr_input($input);

        $ENtable_name = [];

        foreach ($table_name as $table_key) {
            if ($this->getTableName($table_key)) {
                $ENtable_name[] = $this->getTableName($table_key);
            } else {
                $ENtable_name[] = TableName::convert($table_key)->key();
            }
        }

        return response()->json($ENtable_name);
    }

    public function searchApi(Request $request)
    {
        $input = $request->all(['search', 'table']);

        $result = $this->fuzzySearch($input['search'], TableName::convert($input['table'])->key());

        return response()->json($result);
    }

    private function fuzzySearch($input, $table)
    {
        $query = DB::table($table);

        $query->orWhere("service_name", 'LIKE', '%' . $input . '%');

        return $query->get();
    }

    //////////////////////////
    //////////////////////////
    //////////////////////////
    //////////////////////////
    //////////////////////////
    //////////////////////////
    //////////////////////////
    //////////////////////////

    public function test(Request $request)
    {
        $file = $request->file('upload_file');

        $xlsx = $this->resolveXLSX($file);

        $infos = [];
        foreach ([3 => 'op', 4 => 'mng', 5 => 'helper'] as $i => $name) {
            $sheet = $xlsx[$i];

            $infos = array_merge($this->getPageInfo($sheet, $name), $infos);
        }

        $errors = [];

        foreach ($infos as $controller => $methods) {
            foreach ($methods as $key => $method) {
                if ($key == 'No') continue;
                if ($method['page'] == '') continue;

                try {
                    $view = $this->findViewPathFromClassAndMethod(str_replace('app/', '', $controller), $method['method']);
                    $title = $this->findTitleFromView($view);

                    if ($title != $method['show name']) {
                        $errors[$controller][$method['method']] = [
                            'path'        => $view,
                            'code-title'  => $title,
                            'sheet-title' => $method['show name'],
                            'sheet-pos'   => "{$method['sheet']}-{$method['col']}",
                        ];
                    }
                } catch (\Throwable $th) {
                    //$errors[$controller] = [$th];
                }
            }
        }

        dd($errors);
    }

    private function getPageInfo($sheet, $name)
    {
        $infos = [];
        foreach ($sheet as $i => $row) {
            // 1-5行とコントローラー名列が空の行、処理しない
            if ($i < 6) continue;
            if (!array_key_exists('28', $row)) continue;

            if (!array_key_exists($row[28], $infos)) {
                $infos[$row[28]] = [
                    'No' => $row[1] ??  $row[4] ??  $row[7] ??  $row[10] ?? '',
                ];
            }

            $method = [
                'method'     => $row[52] ?? '',
                'type'       => $row[55] ?? '',
                'usecase'    => $row[61] ?? '',
                'url'        => $row[67] ?? '',
                'route name' => $row[103] ?? '',
                'page'       => $row[15] ?? $row[16] ?? $row[17] ?? $row[18] ?? '',
                'show name'  => $row[116] ?? '',
                'sheet'      => $name,
                'col'        => $i,
            ];

            $infos[$row[28]][] = $method;
        }

        return $infos;
    }
    //////////////////////////
    //////////////////////////
    private function findViewPathFromClassAndMethod($className, $methodName): string
    {
        $ast = $this->parsePhpFile(app_path($className));

        $class = $this->findFrom($ast[0]->stmts, \PhpParser\Node\Stmt\Class_::class);

        $methods = $this->findFrom($class->stmts, \PhpParser\Node\Stmt\ClassMethod::class);

        $method = $this->findFrom(
            $methods,
            \PhpParser\Node\Stmt\ClassMethod::class,
            fn($method) => (($method->name->name ?? null) === $methodName) ? true : false,
            (count($methods) == 1 || $methodName === '') ?
                (fn($nodes) => (count($nodes) === 1 || $methodName === '') ? $nodes[0] : null) : null
        );

        $return = $this->findFrom($method->stmts, \PhpParser\Node\Stmt\Return_::class);

        return (($return->expr->args)[0])->value->value;
    }

    private function parsePhpFile(string $path): ?array
    {
        $code = file_get_contents($path);

        $factory = new \PhpParser\ParserFactory();

        $parser = $factory->createForHostVersion();

        try {
            $ast = $parser->parse($code);
        } catch (\PhpParser\Error $e) {
            echo 'Parse Error: ', $e->getMessage();
        }

        return $ast;
    }

    private function findFrom($nodes, $obj, $preprocess = null, $replacement = null)
    {
        if ($replacement) {
            return $replacement($nodes, $obj);
        }

        $results = [];

        foreach ($nodes as $node) {
            if ($node instanceof $obj) {
                if ($preprocess) {
                    if (!$preprocess($node)) {
                        continue;
                    }
                }
                $results[] = $node;
            }
        }

        if (count($results) === 0) {
            return null;
        } elseif (count($results) === 1) {
            return $results[0];
        } else {
            return $results;
        }
    }
    //////////////////////////
    //////////////////////////

    private function findTitleFromView(string $view): string
    {
        $path = View::getFinder()->find($view);

        $viewContent = file_get_contents($path);

        preg_match("/@section\('title', '([^']*)'\)/", $viewContent, $matches);

        return $matches[1];
    }

    private function resolveXLSX($file)
    {
        function excelColumnToNumber(string $column): int
        {
            $column = strtoupper(preg_replace('/\d+/', '', $column));
            $length = strlen($column);
            $number = 0;

            for ($i = 0; $i < $length; $i++) {
                $number *= 26;
                $number += ord($column[$i]) - ord('A') + 1;
            }

            return $number;
        }

        [$lookupTableStrings, $originSheets] = (function () use ($file) {
            $zip = new \ZipArchive;

            $zip->open($file->getRealPath());

            $originSheets = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];

                if ($name === 'xl/sharedStrings.xml') {
                    $sharedStrings = $zip->getFromIndex($i);
                }

                if (str_starts_with($name, 'xl/worksheets/') && !str_starts_with($name, 'xl/worksheets/_rels/')) {
                    $originSheets[] = $zip->getFromIndex($i);
                }
            }
            $zip->close();

            $resolvedSharedStrings = simplexml_load_string($sharedStrings);
            $lookupTableStrings = [];
            foreach (((array)$resolvedSharedStrings)['si'] as $key => $value) {
                if (array_key_exists('t', (array)$value)) {
                    $lookupTableStrings[$key] = ((array)$value)['t'];
                } else {
                    $string = '';
                    foreach (((array)$value)['r'] as $value) {
                        $string .= ((array)$value)['t'];
                    }
                    $lookupTableStrings[$key] = $string;
                }
            }

            return [$lookupTableStrings, $originSheets];
        })();

        $resolvedSheets = [];
        foreach ($originSheets as $originSheet) {
            $sheets = simplexml_load_string($originSheet);

            $resolvedSheet = [];
            foreach ($sheets->sheetData->row as $originRow) {
                $originRow = (array)$originRow;

                $rowIndex = $originRow['@attributes']['r'];

                $row = [];
                if (array_key_exists('c', $originRow)) {
                    foreach ($originRow['c'] as $cell) {
                        $cell = (array)$cell;
                        if (
                            array_key_exists('@attributes', $cell) &&
                            array_key_exists('r', $cell['@attributes']) &&
                            array_key_exists('v', $cell)
                        ) {
                            $cellIndex = excelColumnToNumber($cell['@attributes']['r']);
                            $index = (int)($cell['v']);
                            if ($index <= count($lookupTableStrings)) {
                                $cellValue = $lookupTableStrings[$index];
                            } else {
                                $cellValue = $cell['v'];
                            }

                            $row[$cellIndex] = $cellValue;
                        }
                    }
                    $resolvedSheet[$rowIndex] = $row;
                }
            }
            $resolvedSheets[] = $resolvedSheet;
        }

        return $resolvedSheets;
    }
}
