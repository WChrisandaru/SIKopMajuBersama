<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'bendahara';
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*' => ['required', 'integer', 'distinct', 'exists:bill_items,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::in(['Tunai', 'Transfer'])],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()],
            'verified' => ['accepted'], 'confirmed' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required' => 'Pilih anggota terlebih dahulu.',
            'member_id.exists' => 'Anggota tidak ditemukan.',
            'items.required' => 'Pilih minimal satu komponen tagihan.',
            'items.min' => 'Pilih minimal satu komponen tagihan.',
            'items.max' => 'Pilih maksimal 200 komponen dalam satu pembayaran.',
            'items.*.distinct' => 'Komponen tagihan tidak boleh dipilih dua kali.',
            'items.*.exists' => 'Komponen tagihan tidak ditemukan. Muat ulang tagihan.',
            'amount.*' => 'Total pembayaran harus berupa nominal rupiah yang valid.',
            'method.*' => 'Pilih metode Tunai atau Transfer.',
            'date.*' => 'Tanggal pembayaran harus valid dan tidak boleh melewati hari ini.',
            'verified.accepted' => 'Pastikan dana sudah diterima; transfer harus dicocokkan dengan mutasi rekening.',
            'confirmed.accepted' => 'Periksa ringkasan dan konfirmasikan pembayaran.',
        ];
    }
}
