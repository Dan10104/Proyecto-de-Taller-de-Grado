<?php

return [
    'required' => 'El campo :attribute es obligatorio.', 'present' => 'Debe enviar el campo :attribute.',
    'string' => 'El campo :attribute debe ser texto.', 'email' => 'Ingrese un correo válido.',
    'unique' => 'Ya existe un registro con este :attribute.', 'confirmed' => 'La confirmación de :attribute no coincide.',
    'max' => ['string' => 'El campo :attribute admite hasta :max caracteres.', 'array' => 'El campo :attribute admite hasta :max elementos.', 'numeric' => 'El campo :attribute no puede superar :max.'],
    'min' => ['string' => 'El campo :attribute debe contener al menos :min caracteres.', 'numeric' => 'El campo :attribute debe ser al menos :min.'],
    'in' => 'Seleccione un valor permitido para :attribute.', 'integer' => 'Seleccione un valor válido para :attribute.',
    'exists' => 'El registro seleccionado no existe.', 'array' => 'La selección no es válida.', 'distinct' => 'No repita elementos de :attribute.',
    'prohibited' => 'No tiene permitido modificar :attribute mediante esta operación.',
    'attributes' => ['correo' => 'correo', 'password' => 'contraseña', 'password_confirmation' => 'confirmación de contraseña', 'nombres' => 'nombres', 'apellidos' => 'apellidos', 'nombre' => 'nombre', 'descripcion' => 'descripción', 'usuario_id' => 'usuario', 'rol_id' => 'rol', 'permisos' => 'permisos', 'roles' => 'roles'],
];
