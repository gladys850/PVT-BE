<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateRetirementFundRegulations2024 extends Seeder
{
    public function run()
    {
        // Actualizar submodalidades actuales
        DB::table('procedure_requirements')->where('id', 2533)->update(['number' => 6, 'updated_at' => Carbon::now()]);
        DB::table('procedure_requirements')->where('id', 2543)->update(['number' => 9, 'updated_at' => Carbon::now()]);
        DB::table('procedure_requirements')->where('id', 2571)->update(['number' => 5, 'updated_at' => Carbon::now()]);
        DB::table('procedure_requirements')->where('id', 2561)->update(['number' => 5, 'updated_at' => Carbon::now()]);

        // Insertar nuevos registros para otras modalidades
        DB::table('procedure_requirements')->insert([
            ['procedure_modality_id' => 1, 'procedure_document_id' => 272, 'number' => 11, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['procedure_modality_id' => 2, 'procedure_document_id' => 272, 'number' => 8, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['procedure_modality_id' => 24, 'procedure_document_id' => 272, 'number' => 8, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['procedure_modality_id' => 62, 'procedure_document_id' => 272, 'number' => 5, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['procedure_modality_id' => 63, 'procedure_document_id' => 272, 'number' => 8, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insertar tipos de descuento
        DB::table('discount_types')->insert([
            ['module_id' => 3, 'name' => 'Retención del Beneficio por Autoridad Judicial o Fiscal', 'shortened' => 'Judicial o Fiscal', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Modificar estructura de la tabla discount_type_retirement_fund
        DB::statement('ALTER TABLE discount_type_retirement_fund ADD deleted_at TIMESTAMP NULL');
        DB::statement('ALTER TABLE discount_type_retirement_fund DROP CONSTRAINT discount_type_retirement_fund_discount_type_id_retirement_fund_');

        // Insertar nueva modalidad de liquidación
        DB::table('roles')->insert([
            ['module_id' => 3, 'display_name' => 'Área de Liquidación', 'action' => 'Aprobado', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'name' => 'FR-area-de-liquidacion']
        ]);

        DB::table('wf_states')->insert([
            ['module_id' => 3, 'role_id' => 103, 'name' => 'Área de Liquidación Fondo de Retiro', 'first_shortened' => 'Liquidación', 'sequence_number' => 7, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);

        DB::table('wf_sequences')->insert([
            ['workflow_id' => 4, 'wf_state_current_id' => 24, 'wf_state_next_id' => 62, 'action' => 'Aprobar', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['workflow_id' => 4, 'wf_state_current_id' => 62, 'wf_state_next_id' => 47, 'action' => 'Aprobar', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);        

        // Borrado logico de Resolucion
        DB::table('wf_states')->where('name', 'Área de Resolución Fondo de Retiro')->update(['deleted_at' => Carbon::now()]);
    }
}
