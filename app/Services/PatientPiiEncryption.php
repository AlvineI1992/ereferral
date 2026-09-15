<?php

namespace App\Services;

use App\Models\PatientEncryptionSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\EncryptedField;

class PatientPiiEncryption
{
    public const FIELDS = [
        'patient_master_list' => ['family_id', 'phic_number', 'case_number', 'last_name', 'first_name', 'middle_name', 'suffix', 'birth_date', 'contact_number', 'street_address', 'zip_code'],
        'referral_patientinfo' => ['FamilyID', 'phicNum', 'caseNum', 'patientLastName', 'patientFirstName', 'patientMiddlename', 'patientSuffix', 'patientBirthDate', 'patientContactNumber'],
        'referral_patientdemo' => ['patientStreetAddress', 'patientZipCode'],
    ];

    public const SEARCH_FIELDS = [
        'patient_master_list' => ['family_id', 'phic_number', 'case_number', 'last_name', 'first_name', 'middle_name'],
        'referral_patientinfo' => ['patientLastName', 'patientFirstName', 'patientMiddlename'],
    ];

    public const IDENTITY_FIELDS = [
        'patient_master_list' => ['first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'civil_status'],
        'referral_patientinfo' => ['patientLastName', 'patientFirstName', 'patientMiddlename', 'patientSuffix', 'patientBirthDate', 'patientSex', 'patientCivilStatus'],
    ];

    public function enabled(): bool
    {
        return Schema::hasTable('patient_encryption_settings')
            && in_array(PatientEncryptionSetting::query()->value('status'), ['converting', 'active', 'failed'], true);
    }

    public function encrypted(mixed $value): bool
    {
        return is_string($value) && preg_match('/^(nacl|brng|fips):/', $value) === 1;
    }

    private function field(string $table, string $column): EncryptedField
    {
        return new EncryptedField(app(CipherSweet::class), $table, $column);
    }

    public function encrypt(string $table, string $column, mixed $value, string $aad = ''): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->field($table, $column)->encryptValue((string) $value, $aad);
    }

    public function decrypt(string $table, string $column, mixed $value, string $aad = ''): mixed
    {
        // Only legacy plaintext is accepted as-is. Invalid ciphertext must fail closed.
        return $this->encrypted($value) ? $this->field($table, $column)->decryptValue($value, $aad) : $value;
    }

    public function decryptRecord(string $table, object $row): object
    {
        $result = clone $row;
        foreach ((array) $row as $column => $value) {
            foreach (self::FIELDS[$table] ?? [] as $field) {
                if (strcasecmp($column, $field) === 0) {
                    $result->{$column} = $this->decrypt($table, $field, $value);
                }
            }
        }

        return $result;
    }

    public function snapshot(array $snapshot, string $logId): string
    {
        $json = json_encode($snapshot, JSON_THROW_ON_ERROR);

        return $this->enabled() ? $this->encrypt('referral_pathway_steps', 'snapshot', $json, $logId) : $json;
    }

    public function readSnapshot(string $snapshot, string $logId): array
    {
        return json_decode($this->decrypt('referral_pathway_steps', 'snapshot', $snapshot, $logId), true, 512, JSON_THROW_ON_ERROR);
    }

    public function normalize(mixed $value): string
    {
        return mb_strtoupper(trim((string) ($value ?? '')), 'UTF-8');
    }

    public function index(string $table, string $name, string $value): string
    {
        return $this->field($table, $name)
            ->addBlindIndex(new BlindIndex('exact', [], 128))
            ->getBlindIndex($value, 'exact');
    }

    private function identity(string $table, array $values): string
    {
        return json_encode(array_map(fn ($column) => $this->normalize($values[$column] ?? null), self::IDENTITY_FIELDS[$table]), JSON_THROW_ON_ERROR);
    }

    public function syncIndexes(Model $model): void
    {
        if (! $this->enabled()) {
            return;
        }
        $table = $model->getTable();
        $values = (array) $this->decryptRecord($table, (object) $model->getAttributes());
        $indexes = [];
        foreach (self::SEARCH_FIELDS[$table] ?? [] as $column) {
            $value = $this->normalize($values[$column] ?? null);
            if ($value !== '') {
                $indexes[$column] = $value;
            }
        }
        if (isset(self::IDENTITY_FIELDS[$table])) {
            $indexes['identity'] = $this->identity($table, $values);
        }
        DB::table('patient_pii_indexes')->where('table_name', $table)->where('record_id', (string) $model->getKey())->delete();
        foreach ($indexes as $name => $value) {
            DB::table('patient_pii_indexes')->insert([
                'table_name' => $table, 'record_id' => (string) $model->getKey(),
                'name' => $name, 'value' => $this->index($table, $name, $value),
            ]);
        }
    }

    public function whereIdentity(Builder $query, array $values): Builder
    {
        $table = $query->getModel()->getTable();
        return $query->where(function (Builder $match) use ($table, $values) {
            if ($this->enabled()) {
                $this->indexed($match, 'identity', $this->identity($table, $values));
            }
            $method = $this->enabled() ? 'orWhere' : 'where';
            $match->{$method}(function (Builder $plain) use ($table, $values) {
                foreach (self::IDENTITY_FIELDS[$table] as $column) {
                    $plain->whereRaw("UPPER(TRIM(COALESCE({$table}.{$column}, ''))) = ?", [$this->normalize($values[$column] ?? null)]);
                }
            });
        });
    }

    public function search(Builder $query, string $search, ?array $columns = null): Builder
    {
        $table = $query->getModel()->getTable();
        $columns ??= self::SEARCH_FIELDS[$table];
        return $query->where(function (Builder $match) use ($table, $search, $columns) {
            foreach ($columns as $column) {
                if (! in_array($column, self::SEARCH_FIELDS[$table], true)) {
                    throw new \InvalidArgumentException('Unsupported patient search field.');
                }
                if ($this->enabled()) {
                    $match->orWhere(fn (Builder $index) => $this->indexed($index, $column, $this->normalize($search)));
                    $match->orWhereRaw("UPPER(TRIM({$table}.{$column})) = ?", [$this->normalize($search)]);
                } else {
                    $match->orWhereRaw("UPPER({$table}.{$column}) LIKE ?", ['%'.$this->normalize($search).'%']);
                }
            }
        });
    }

    private function indexed(Builder $query, string $name, string $value): void
    {
        $model = $query->getModel();
        $query->whereIn($model->qualifyColumn($model->getKeyName()), DB::table('patient_pii_indexes')
            ->select('record_id')->where('table_name', $model->getTable())->where('name', $name)
            ->where('value', $this->index($model->getTable(), $name, $value)));
    }
}
