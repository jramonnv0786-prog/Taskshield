-- TaskShield - Script de la base de datos
-- Crea la base de datos, sus tablas y los datos de prueba.


-- Base de datos
-- Se crea solo si no existe y se selecciona para trabajar en ella.
-- ---------------------------------------------------------
CREATE DATABASE IF NOT EXISTS taskshield
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;

USE taskshield;

-- Borrado de tablas (primero la hija, luego la madre)
-- incidencias depende de tecnicos por la clave foránea,
-- así que se borra antes.

DROP TABLE IF EXISTS incidencias;
DROP TABLE IF EXISTS tecnicos;


-- Tabla tecnicos
-- tareas_activas cuenta las incidencias "En Proceso" de cada
-- técnico. El CHECK limita la carga a 5 tareas: es lo que hace
-- fallar la 2.ª consulta de la transacción en el caso de prueba 3.

CREATE TABLE tecnicos (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre         VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  tareas_activas INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT pk_tecnicos PRIMARY KEY (id),
  CONSTRAINT uq_tecnicos_email UNIQUE (email),
  CONSTRAINT chk_tecnicos_tareas CHECK (tareas_activas <= 5)
) ENGINE=InnoDB;


-- Tabla incidencias
-- tecnico_id admite NULL: una incidencia nueva aún no tiene
-- técnico. Si se borra un técnico, sus incidencias se quedan
-- sin asignar (ON DELETE SET NULL) en lugar de borrarse.

CREATE TABLE incidencias (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo         VARCHAR(150) NOT NULL,
  descripcion    TEXT NOT NULL,
  prioridad      ENUM('Baja', 'Media', 'Alta') NOT NULL DEFAULT 'Media',
  email_cliente  VARCHAR(150) NOT NULL,
  estado         ENUM('Pendiente', 'En Proceso', 'Resuelta') NOT NULL DEFAULT 'Pendiente',
  tecnico_id     INT UNSIGNED NULL DEFAULT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pk_incidencias PRIMARY KEY (id),
  CONSTRAINT fk_incidencias_tecnico FOREIGN KEY (tecnico_id)
    REFERENCES tecnicos (id)
    ON DELETE SET NULL
) ENGINE=InnoDB;


-- Datos de prueba
-- Técnicos con 0, 2 y 5 tareas (5 = al límite, para el caso de
-- prueba 3). Los contadores coinciden con las incidencias
-- "En Proceso" de cada uno. Tras el DROP, los id empiezan en 1:
-- Ana = 1, Luis = 2, Marta = 3.

INSERT INTO tecnicos (nombre, email, tareas_activas) VALUES
  ('Ana Ruiz',    'ana.ruiz@taskshield.local',    0),
  ('Luis Gómez',  'luis.gomez@taskshield.local',  2),
  ('Marta Díaz',  'marta.diaz@taskshield.local',  5);

INSERT INTO incidencias (titulo, descripcion, prioridad, email_cliente, estado, tecnico_id) VALUES
  -- Pendientes, sin técnico (para probar la asignación)
  ('Impresora no imprime',     'La impresora de la 2.ª planta muestra error de papel atascado.', 'Media', 'contabilidad@cliente.example', 'Pendiente',  NULL),
  ('Sin acceso al correo',     'Outlook pide la contraseña continuamente.',                      'Alta',  'rrhh@cliente.example',         'Pendiente',  NULL),
  ('Pantalla parpadea',        'El monitor del puesto 14 parpadea al encenderse.',               'Baja',  'ventas@cliente.example',       'Pendiente',  NULL),
  -- En proceso de Luis (2)
  ('Wifi lento en sala B',     'La conexión inalámbrica cae varias veces al día.',               'Media', 'direccion@cliente.example',    'En Proceso', 2),
  ('Instalar antivirus',       'Equipo nuevo de recepción sin antivirus.',                       'Alta',  'recepcion@cliente.example',    'En Proceso', 2),
  -- En proceso de Marta (5, al límite)
  ('Ratón no responde',        'El ratón inalámbrico no se conecta.',                            'Baja',  'ventas@cliente.example',       'En Proceso', 3),
  ('Error al abrir el ERP',    'El ERP se cierra al iniciar sesión.',                            'Alta',  'contabilidad@cliente.example', 'En Proceso', 3),
  ('Teclado con teclas rotas', 'Faltan las teclas Ñ y Enter.',                                   'Baja',  'rrhh@cliente.example',         'En Proceso', 3),
  ('Copia de seguridad falla', 'La copia nocturna del servidor termina con error.',              'Alta',  'direccion@cliente.example',    'En Proceso', 3),
  ('Proyector sin señal',      'El proyector de la sala de reuniones no detecta el portátil.',   'Media', 'marketing@cliente.example',    'En Proceso', 3),
  -- Resuelta (no cuenta como activa ni aparece en el panel)
  ('Cambio de contraseña',     'Restablecer la contraseña de un usuario bloqueado.',             'Media', 'rrhh@cliente.example',         'Resuelta',   2);
