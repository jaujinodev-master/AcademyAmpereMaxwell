# 🚀 Guía de Instalación - Academia Ampere Maxwell

## Requisitos Previos

- ✅ XAMPP instalado (Apache + MySQL + PHP 7.4+)
- ✅ Navegador web moderno (Chrome, Firefox, Edge)

---

## 📦 Paso 1: Mover el Proyecto a XAMPP

1. Copia la carpeta completa del proyecto:
   ```
   Origen: c:\Users\JaujinoDev\Downloads\AcademyAmpereMaxwell
   Destino: C:\xampp\htdocs\AcademyAmpereMaxwell
   ```

2. Verifica que la estructura de carpetas sea:
   ```
   C:\xampp\htdocs\AcademyAmpereMaxwell\
   ├── index.html
   ├── assets/
   ├── intranet/
   ├── database/
   └── .htaccess
   ```

---

## 🔧 Paso 2: Iniciar Servicios de XAMPP

1. Abre el **Panel de Control de XAMPP**
2. Haz clic en **Start** junto a **Apache**
3. Haz clic en **Start** junto a **MySQL**
4. Verifica que ambos servicios muestren el estado en verde

---

## 🗄️ Paso 3: Crear la Base de Datos

### Opción A: Importar desde phpMyAdmin (Recomendado)

1. Abre tu navegador y ve a: **http://localhost/phpmyadmin**

2. En el panel izquierdo, haz clic en **"Nuevo"** o **"New"**

3. Haz clic en la pestaña **"Importar"** o **"Import"**

4. Haz clic en **"Seleccionar archivo"** o **"Choose File"**

5. Navega a: `C:\xampp\htdocs\AcademyAmpereMaxwell\database\schema.sql`

6. Haz clic en **"Continuar"** o **"Go"** en la parte inferior

7. Espera a que aparezca el mensaje de éxito

### Opción B: Ejecutar desde línea de comandos

```bash
cd C:\xampp\mysql\bin
mysql -u root -p < C:\xampp\htdocs\AcademyAmpereMaxwell\database\schema.sql
```
(Presiona Enter cuando pida contraseña, por defecto está vacía)

---

## ✅ Paso 4: Verificar la Instalación

1. **Verificar la Landing Page:**
   - Abre: **http://localhost/AcademyAmpereMaxwell**
   - Deberías ver la página de inicio de la academia

2. **Verificar la Página de Login:**
   - Abre: **http://localhost/AcademyAmpereMaxwell/intranet/login.php**
   - Deberías ver la página de login con diseño moderno

3. **Verificar la Base de Datos:**
   - Ve a: **http://localhost/phpmyadmin**
   - En el panel izquierdo, busca la base de datos: **academia_ampere_maxwell**
   - Haz clic en ella y verifica que tenga 15 tablas

---

## 🔑 Credenciales de Acceso

### Usuario Administrador por Defecto

- **Usuario:** `admin`
- **Contraseña:** `admin123`
- **Email:** `admin@ampere-maxwell.edu.pe`

> ⚠️ **Importante:** Cambia esta contraseña después del primer inicio de sesión.

---

## 📊 Estructura de la Base de Datos

La base de datos incluye las siguientes tablas:

### Tablas Principales
1. **roles** - Roles de usuario (Admin, Profesor, Alumno, Servicios)
2. **usuarios** - Información de todos los usuarios
3. **ciclos_academicos** - Ciclos educativos
4. **cursos** - Cursos disponibles
5. **matriculas** - Matrículas de alumnos
6. **inscripciones_curso** - Inscripciones a cursos específicos

### Tablas Académicas
7. **calificaciones** - Notas de los alumnos
8. **asistencias** - Registro de asistencias
9. **materiales_educativos** - Materiales subidos por profesores
10. **tareas** - Tareas asignadas
11. **entregas_tareas** - Entregas de tareas de alumnos

### Tablas de Soporte
12. **profesor_curso** - Asignación de profesores a cursos
13. **horarios** - Horarios de clases

### Vistas y Procedimientos
- **vista_alumnos_ciclo** - Resumen de alumnos por ciclo
- **vista_promedios_alumnos** - Promedios de calificaciones
- **sp_calcular_promedio_curso** - Calcular promedio de un curso
- **sp_porcentaje_asistencia** - Calcular porcentaje de asistencia

---

## 🔍 Solución de Problemas

### Apache no inicia
- **Problema:** Puerto 80 ocupado por otro servicio
- **Solución:** 
  1. Abre `C:\xampp\apache\conf\httpd.conf`
  2. Busca `Listen 80` y cámbialo a `Listen 8080`
  3. Reinicia Apache
  4. Accede con: `http://localhost:8080/AcademyAmpereMaxwell`

### MySQL no inicia
- **Problema:** Puerto 3306 ocupado
- **Solución:**
  1. Abre `C:\xampp\mysql\bin\my.ini`
  2. Busca `port=3306` y cámbialo a `port=3307`
  3. Actualiza `intranet/config/database.php` con el nuevo puerto

### Error de conexión a la base de datos
- **Verificar que MySQL esté corriendo en XAMPP**
- **Verificar que la base de datos existe en phpMyAdmin**
- **Verificar las credenciales en:** `intranet/config/database.php`

### Página en blanco o error 404
- **Verificar que la carpeta esté en:** `C:\xampp\htdocs\AcademyAmpereMaxwell`
- **Verificar que Apache esté corriendo**
- **Verificar la URL:** `http://localhost/AcademyAmpereMaxwell` (con mayúsculas)

---

## 📝 Próximos Pasos

Una vez completada la instalación:

1. ✅ Probar el login con las credenciales de administrador
2. ✅ Explorar la base de datos en phpMyAdmin
3. ✅ Continuar con la Fase 3: Sistema de Autenticación
4. ✅ Desarrollar los dashboards para cada tipo de usuario

---

## 📞 Soporte

Si encuentras algún problema durante la instalación, verifica:
- Logs de Apache: `C:\xampp\apache\logs\error.log`
- Logs de MySQL: `C:\xampp\mysql\data\mysql_error.log`
- Logs de PHP: Habilitados en `php.ini`

---

## 🎉 ¡Instalación Completada!

Si todos los pasos anteriores funcionaron correctamente, tu plataforma educativa está lista para comenzar el desarrollo de las funcionalidades de autenticación y dashboards.
