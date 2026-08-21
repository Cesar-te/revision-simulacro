<?php

namespace Database\Seeders;

use App\Models\Career;
use Illuminate\Database\Seeder;

class CareerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $careers = [
            // GRUPO A: CIENCIAS MÉDICAS / BIOMÉDICAS
            ['name' => 'Medicina Humana', 'academic_group' => 'A', 'group_label' => 'Ciencias Médicas (Biomédicas)'],
            ['name' => 'Enfermería', 'academic_group' => 'A', 'group_label' => 'Ciencias Médicas (Biomédicas)'],
            ['name' => 'Biología', 'academic_group' => 'A', 'group_label' => 'Ciencias Médicas (Biomédicas)'],
            ['name' => 'Biología - Microbiología y Parasitología', 'academic_group' => 'A', 'group_label' => 'Ciencias Médicas (Biomédicas)'],
            ['name' => 'Biología - Botánica', 'academic_group' => 'A', 'group_label' => 'Ciencias Médicas (Biomédicas)'],
            ['name' => 'Biología - Pesquería', 'academic_group' => 'A', 'group_label' => 'Ciencias Médicas (Biomédicas)'],
            ['name' => 'Medicina Veterinaria', 'academic_group' => 'A', 'group_label' => 'Ciencias Médicas (Biomédicas)'],

            // GRUPO B, C, D: LETRAS / CS. SOCIALES / HUMANIDADES / ECONÓMICAS
            ['name' => 'Derecho', 'academic_group' => 'BCD', 'group_label' => 'Letras / Sociales y Políticas'],
            ['name' => 'Ciencia Política', 'academic_group' => 'BCD', 'group_label' => 'Letras / Sociales y Políticas'],
            ['name' => 'Administración', 'academic_group' => 'BCD', 'group_label' => 'Letras / Económicas'],
            ['name' => 'Contabilidad', 'academic_group' => 'BCD', 'group_label' => 'Letras / Económicas'],
            ['name' => 'Economía', 'academic_group' => 'BCD', 'group_label' => 'Letras / Económicas'],
            ['name' => 'Comercio y Negocios Internacionales', 'academic_group' => 'BCD', 'group_label' => 'Letras / Económicas'],
            ['name' => 'Sociología', 'academic_group' => 'BCD', 'group_label' => 'Letras / Sociales'],
            ['name' => 'Ciencias de la Comunicación', 'academic_group' => 'BCD', 'group_label' => 'Letras / Sociales'],
            ['name' => 'Arqueología', 'academic_group' => 'BCD', 'group_label' => 'Letras / Sociales'],
            ['name' => 'Psicología', 'academic_group' => 'BCD', 'group_label' => 'Letras / Sociales'],
            ['name' => 'Artes - Artes Plásticas', 'academic_group' => 'BCD', 'group_label' => 'Letras / Artes'],
            ['name' => 'Artes - Danzas', 'academic_group' => 'BCD', 'group_label' => 'Letras / Artes'],
            ['name' => 'Artes - Música', 'academic_group' => 'BCD', 'group_label' => 'Letras / Artes'],
            ['name' => 'Artes - Teatro', 'academic_group' => 'BCD', 'group_label' => 'Letras / Artes'],
            ['name' => 'Educación - Educación Inicial', 'academic_group' => 'BCD', 'group_label' => 'Letras / Educación'],
            ['name' => 'Educación - Educación Primaria', 'academic_group' => 'BCD', 'group_label' => 'Letras / Educación'],
            ['name' => 'Educación - Ciencias Histórico-Sociales y Filosofía', 'academic_group' => 'BCD', 'group_label' => 'Letras / Educación'],
            ['name' => 'Educación - Educación Física', 'academic_group' => 'BCD', 'group_label' => 'Letras / Educación'],
            ['name' => 'Educación - Idiomas Extranjeros', 'academic_group' => 'BCD', 'group_label' => 'Letras / Educación'],
            ['name' => 'Educación - Lengua y Literatura', 'academic_group' => 'BCD', 'group_label' => 'Letras / Educación'],
            ['name' => 'Educación - Matemáticas y Computación', 'academic_group' => 'BCD', 'group_label' => 'Letras / Educación'],
            ['name' => 'Educación - Ciencias Naturales', 'academic_group' => 'BCD', 'group_label' => 'Letras / Educación'],

            // GRUPO E, F: INGENIERÍAS Y CIENCIAS EXACTAS / AGROPECUARIAS
            ['name' => 'Ingeniería Civil', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Ingeniería de Sistemas', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Ingeniería Industrial', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Ingeniería Mecánica y Eléctrica', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Ingeniería Química', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Ingeniería Electrónica', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Arquitectura', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Física', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Matemáticas', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Estadística', 'academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'],
            ['name' => 'Ingeniería Agrícola', 'academic_group' => 'EF', 'group_label' => 'Ingenierías Agropecuarias'],
            ['name' => 'Agronomía', 'academic_group' => 'EF', 'group_label' => 'Ingenierías Agropecuarias'],
            ['name' => 'Ingeniería en Industrias Alimentarias', 'academic_group' => 'EF', 'group_label' => 'Ingenierías Agropecuarias'],
            ['name' => 'Ingeniería Zootecnia', 'academic_group' => 'EF', 'group_label' => 'Ingenierías Agropecuarias'],
            ['name' => 'Zootecnia', 'academic_group' => 'EF', 'group_label' => 'Ingenierías Agropecuarias'],
            ['name' => 'Ingeniería Forestal y del Medio Ambiente', 'academic_group' => 'EF', 'group_label' => 'Ingenierías Agropecuarias'],
        ];

        foreach ($careers as $career) {
            Career::updateOrCreate(['name' => $career['name']], $career);
        }
    }
}
