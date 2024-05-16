<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CardRequest extends FormRequest
{
    private $team;

    public function setTeam($team)
    {
        $this->team = $team;
    }

    /**
     * Determine if the team is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $cardId = $this->card ? $this->card->id : null;

        return [
            'title' => 'required|unique:cards,title,' . $cardId . ',id,board_section_id,' . $this->board_section_id,
            'description' => 'required|unique:cards,description,' . $cardId . ',id,board_section_id,' . $this->board_section_id,
            'board_section_id' => 'required|exists:board_sections,id',
            'position_id' => 'required|unique:cards,position_id,NULL,id,board_section_id,' . $this->board_section_id,
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
