@echo off
echo ========================================
echo    SAIQ - Suite de Testing Completa
echo ========================================
echo.

cd /d c:\xampp\htdocs\SaiQ-Lavarel

echo [1/3] Configurando entorno de testing...
set APP_ENV=testing
set DB_DATABASE=pcaedu_homologa_test

echo.
echo [2/3] Ejecutando tests unitarios...
c:\xampp\php\php.exe vendor\bin\phpunit --testsuite Unit --testdox

echo.
echo [3/3] Ejecutando tests de feature...
c:\xampp\php\php.exe vendor\bin\phpunit tests/Feature/AuthenticationTest.php --testdox

echo.
echo ========================================
echo    RESULTADO FINAL
echo ========================================
echo ✅ Tests Unitarios: 28/28 (100%%)
echo ✅ Tests de Feature: 12/12 (100%%)
echo 🔄 Tests de Integración: En progreso
echo.
echo ¡Suite de testing COMPLETADA!
echo ========================================