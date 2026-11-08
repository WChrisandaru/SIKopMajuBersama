<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'bendahara';
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'min:1', 'exists:members,id'],
            'name' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'string', 'regex:/^[0-9]{16}$/', Rule::unique('members', 'nik')->ignore($this->integer('id'))],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()-]{5,28}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['Aktif', 'Nonaktif'])],
            'bank_name' => ['nullable', 'required_with:account_number,account_holder', 'string', 'max:150'],
            'account_number' => ['nullable', 'required_with:bank_name,account_holder', 'string', 'regex:/^[0-9]{1,34}$/'],
            'account_holder' => ['nullable', 'required_with:bank_name,account_number', 'string', 'max:150'],
            'emergency_name' => ['nullable', 'required_with:emergency_phone,emergency_relationship', 'string', 'max:150'],
            'emergency_phone' => ['nullable', 'required_with:emergency_name,emergency_relationship', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()-]{5,28}$/'],
            'emergency_relationship' => ['nullable', 'required_with:emergency_name,emergency_phone', Rule::in(['Suami/Istri', 'Orang Tua', 'Anak', 'Saudara Kandung', 'Lainnya'])],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_with' => 'Lengkapi :attribute agar data pada bagian ini utuh.',
            'max' => ':attribute maksimal :max karakter.',
            'string' => ':attribute harus berupa teks.',
            'nik.regex' => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique' => 'NIK sudah digunakan oleh anggota lain.',
            'account_number.regex' => 'Nomor rekening hanya boleh berisi angka, maksimal 34 digit.',
            'contact.regex' => 'Gunakan nomor kontak yang valid, misalnya 081234567890 atau +6281234567890.',
            'emergency_phone.regex' => 'Gunakan nomor telepon darurat yang valid.',
            'id.exists' => 'Anggota tidak ditemukan. Muat ulang daftar anggota.',
            'id.integer' => 'Identitas anggota tidak valid.',
            'id.min' => 'Identitas anggota tidak valid.',
            'in' => 'Pilihan :attribute tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap', 'nik' => 'NIK', 'mother_name' => 'nama ibu kandung',
            'contact' => 'kontak utama', 'address' => 'alamat', 'status' => 'status anggota',
            'bank_name' => 'nama bank', 'account_number' => 'nomor rekening', 'account_holder' => 'nama pemilik rekening',
            'emergency_name' => 'nama kontak darurat', 'emergency_phone' => 'nomor kontak darurat', 'emergency_relationship' => 'hubungan keluarga',
        ];
    }
}
