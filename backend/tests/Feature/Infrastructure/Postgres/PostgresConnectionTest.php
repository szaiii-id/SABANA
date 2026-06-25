<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Postgres;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostgresConnectionTest extends TestCase
{
    use RefreshDatabase;

    // ===== HELPER =====

    private function validData(): array
    {
        return [
            'nik' => '6371012305900001',
            'nama' => 'Test User',
            'status' => 'aktif',
        ];
    }

    // ===== SCENARIO 1: HAPPY PATH =====

    /** @test */
    public function test_happy_path_database_connection_select_one(): void
    {
        $result = DB::connection()->getPdo()->query('SELECT 1');

        $this->assertEquals(1, $result->fetchColumn());
    }

    /** @test */
    public function test_happy_path_migrations_table_exists(): void
    {
        $exists = DB::table('migrations')->exists();

        $this->assertTrue($exists);
    }

    /** @test */
    public function test_happy_path_can_create_temp_table_and_insert(): void
    {
        DB::statement('CREATE TEMP TABLE test_happy (id SERIAL PRIMARY KEY, name VARCHAR(100))');
        DB::insert('INSERT INTO test_happy (name) VALUES (?)', ['SABANA Test']);

        $result = DB::selectOne('SELECT name FROM test_happy LIMIT 1');

        $this->assertEquals('SABANA Test', $result->name);
    }

    // ===== SCENARIO 2: SAD PATH =====

    /** @test */
    public function test_sad_path_query_non_existent_table_throws_exception(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('non_existent_table_' . uniqid())->get();
    }

    /** @test */
    public function test_sad_path_duplicate_primary_key_throws_exception(): void
    {
        DB::statement('CREATE TEMP TABLE test_dup (id INTEGER PRIMARY KEY, val TEXT)');
        DB::insert('INSERT INTO test_dup (id, val) VALUES (?, ?)', [1, 'first']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::insert('INSERT INTO test_dup (id, val) VALUES (?, ?)', [1, 'duplicate']);
    }

    // ===== SCENARIO 3: BOUNDARY =====

    /** @test */
    public function test_boundary_max_varchar_255_stored(): void
    {
        DB::statement('CREATE TEMP TABLE test_boundary (value VARCHAR(255))');
        $longString = str_repeat('a', 255);

        DB::insert('INSERT INTO test_boundary (value) VALUES (?)', [$longString]);
        $result = DB::selectOne('SELECT value FROM test_boundary LIMIT 1');

        $this->assertEquals(255, strlen($result->value));
    }

    /** @test */
    public function test_boundary_max_integer_bigint(): void
    {
        DB::statement('CREATE TEMP TABLE test_bigint (value BIGINT)');
        $bigNumber = 9223372036854775807; // Max BIGINT

        DB::insert('INSERT INTO test_bigint (value) VALUES (?)', [$bigNumber]);
        $result = DB::selectOne('SELECT value FROM test_bigint LIMIT 1');

        $this->assertEquals($bigNumber, (int) $result->value);
    }

    /** @test */
    public function test_boundary_empty_string_vs_null(): void
    {
        DB::statement('CREATE TEMP TABLE test_empty (val TEXT)');

        DB::insert('INSERT INTO test_empty (val) VALUES (?)', ['']);
        $result = DB::selectOne('SELECT val FROM test_empty LIMIT 1');

        $this->assertSame('', $result->val);
        $this->assertNotNull($result->val);
    }

    // ===== SCENARIO 4: EDGE CASE =====

    /** @test */
    public function test_edge_case_unicode_characters_stored(): void
    {
        DB::statement('CREATE TEMP TABLE test_unicode (name TEXT)');
        $unicode = '試験テスト🎉中文日本語';

        DB::insert('INSERT INTO test_unicode (name) VALUES (?)', [$unicode]);
        $result = DB::selectOne('SELECT name FROM test_unicode LIMIT 1');

        $this->assertEquals($unicode, $result->name);
    }

    /** @test */
    public function test_edge_case_json_special_characters(): void
    {
        DB::statement('CREATE TEMP TABLE test_json_special (data JSON)');
        $special = json_encode([
            'quote' => "O'Brien \"test\"",
            'backslash' => 'C:\\path\\file',
            'unicode' => '🎉',
        ]);

        DB::insert('INSERT INTO test_json_special (data) VALUES (?)', [$special]);
        $result = DB::selectOne('SELECT data FROM test_json_special LIMIT 1');

        $decoded = json_decode($result->data, true);
        $this->assertEquals("O'Brien \"test\"", $decoded['quote']);
    }

    // ===== SCENARIO 5: NULL/EMPTY =====

    /** @test */
    public function test_null_empty_null_insert_and_retrieve(): void
    {
        DB::statement('CREATE TEMP TABLE test_null (id SERIAL PRIMARY KEY, value TEXT)');
        DB::insert('INSERT INTO test_null (value) VALUES (NULL)');

        $result = DB::selectOne('SELECT value FROM test_null LIMIT 1');

        $this->assertNull($result->value);
    }

    /** @test */
    public function test_null_empty_coalesce_fallback(): void
    {
        DB::statement('CREATE TEMP TABLE test_coalesce (value TEXT)');
        DB::insert('INSERT INTO test_coalesce (value) VALUES (NULL)');

        $result = DB::selectOne(
            'SELECT COALESCE(value, ?) AS value FROM test_coalesce LIMIT 1',
            ['default_value']
        );

        $this->assertEquals('default_value', $result->value);
    }

    // ===== SCENARIO 6: DATA TYPE =====

    /** @test */
    public function test_data_type_boolean_true_false_preserved(): void
    {
        DB::statement('CREATE TEMP TABLE test_bool (flag BOOLEAN)');

        DB::insert('INSERT INTO test_bool (flag) VALUES (?)', [true]);
        DB::insert('INSERT INTO test_bool (flag) VALUES (?)', [false]);

        $results = DB::select('SELECT flag FROM test_bool ORDER BY flag DESC');

        $this->assertTrue((bool) $results[0]->flag);
        $this->assertFalse((bool) $results[1]->flag);
    }

    /** @test */
    public function test_data_type_timestamp_with_timezone(): void
    {
        DB::statement('CREATE TEMP TABLE test_tz (created TIMESTAMPTZ)');
        $now = now();

        DB::insert('INSERT INTO test_tz (created) VALUES (?)', [$now]);
        $result = DB::selectOne('SELECT created FROM test_tz LIMIT 1');

        $this->assertEquals($now->timestamp, strtotime($result->created));
    }

    /** @test */
    public function test_data_type_json_column_preserved(): void
    {
        DB::statement('CREATE TEMP TABLE test_json (meta JSON)');
        $data = ['key' => 'value', 'nested' => ['a' => 1]];

        DB::insert('INSERT INTO test_json (meta) VALUES (?)', [json_encode($data)]);
        $result = DB::selectOne('SELECT meta FROM test_json LIMIT 1');

        $this->assertEquals($data, json_decode($result->meta, true));
    }

    // ===== SCENARIO 7: EQUIVALENCE PARTITION =====

    /** @test */
    public function test_equivalence_transaction_commit_persists_data(): void
    {
        DB::statement('CREATE TEMP TABLE test_tx_commit (id SERIAL PRIMARY KEY, val TEXT)');

        DB::beginTransaction();
        DB::insert('INSERT INTO test_tx_commit (val) VALUES (?)', ['committed']);
        DB::commit();

        $count = DB::table('test_tx_commit')->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function test_equivalence_transaction_rollback_discards_data(): void
    {
        DB::statement('CREATE TEMP TABLE test_tx_rollback (id SERIAL PRIMARY KEY, val TEXT)');

        DB::beginTransaction();
        DB::insert('INSERT INTO test_tx_rollback (val) VALUES (?)', ['rolled_back']);
        DB::rollBack();

        $count = DB::table('test_tx_rollback')->count();
        $this->assertEquals(0, $count);
    }

    // ===== SCENARIO 8: STATE TRANSITION =====

    /** @test */
    public function test_state_transition_insert_update_delete_cycle(): void
    {
        DB::statement('CREATE TEMP TABLE test_state (id SERIAL PRIMARY KEY, status TEXT)');

        // Insert
        DB::insert('INSERT INTO test_state (status) VALUES (?)', ['pending']);
        $inserted = DB::selectOne('SELECT id, status FROM test_state LIMIT 1');
        $this->assertEquals('pending', $inserted->status);

        // Update
        DB::update('UPDATE test_state SET status = ? WHERE id = ?', ['verified', $inserted->id]);
        $updated = DB::selectOne('SELECT status FROM test_state WHERE id = ?', [$inserted->id]);
        $this->assertEquals('verified', $updated->status);

        // Delete
        DB::delete('DELETE FROM test_state WHERE id = ?', [$inserted->id]);
        $count = DB::table('test_state')->count();
        $this->assertEquals(0, $count);
    }

    // ===== SCENARIO 9: CONCURRENCY =====

    /** @test */
    public function test_concurrency_multiple_inserts_in_transaction(): void
    {
        DB::statement('CREATE TEMP TABLE test_concurrent (id SERIAL PRIMARY KEY, value INT)');

        DB::beginTransaction();
        for ($i = 0; $i < 10; $i++) {
            DB::insert('INSERT INTO test_concurrent (value) VALUES (?)', [$i]);
        }
        DB::commit();

        $count = DB::table('test_concurrent')->count();
        $this->assertEquals(10, $count);
    }

    /** @test */
    public function test_concurrency_row_locking_with_for_update(): void
    {
        DB::statement('CREATE TEMP TABLE test_lock (id SERIAL PRIMARY KEY, balance INT)');
        DB::insert('INSERT INTO test_lock (id, balance) VALUES (?, ?)', [1, 100]);

        DB::beginTransaction();
        $row = DB::table('test_lock')->where('id', 1)->lockForUpdate()->first();
        $this->assertEquals(100, $row->balance);

        DB::update('UPDATE test_lock SET balance = ? WHERE id = ?', [$row->balance - 50, 1]);
        DB::commit();

        $updated = DB::table('test_lock')->where('id', 1)->first();
        $this->assertEquals(50, $updated->balance);
    }

    // ===== SCENARIO 10: SECURITY =====

    /** @test */
    public function test_security_connection_uses_ssl_or_localhost(): void
    {
        $sslEnabled = DB::connection()->getConfig('sslmode') === 'require'
            || DB::connection()->getConfig('sslmode') === 'verify-full';

        // Skip SSL check di local/development
        if (app()->environment('local', 'testing', 'development')) {
            $this->markTestSkipped('SSL not required in local/development environment.');
            return;
        }

        $this->assertTrue($sslEnabled, 'Production database harus menggunakan SSL');
    }
    
    /** @test */
    public function test_security_sql_injection_prevented_by_parameter_binding(): void
    {
        DB::statement('CREATE TEMP TABLE test_sqli (name TEXT)');

        $maliciousInput = "'; DROP TABLE test_sqli; --";

        // Parameter binding mencegah SQL injection
        DB::insert('INSERT INTO test_sqli (name) VALUES (?)', [$maliciousInput]);

        $result = DB::selectOne('SELECT name FROM test_sqli LIMIT 1');

        $this->assertEquals($maliciousInput, $result->name);
        // Tabel masih ada = SQL injection gagal
        $count = DB::table('test_sqli')->count();
        $this->assertEquals(1, $count);
    }
}