-- =====================================================
-- SCRIPTS DE VALIDACIÓN DE DUPLICADOS
-- Base de Datos: pcaedu_homologa
-- =====================================================

-- 1. USUARIOS DUPLICADOS
SELECT '--- USUARIOS DUPLICADOS ---' as "Reporte";
SELECT id, COUNT(*) as cantidad FROM usuario GROUP BY id HAVING COUNT(*) > 1;

-- 2. USUARIOS CON EMAIL DUPLICADO
SELECT '--- USUARIOS CON EMAIL DUPLICADO ---' as "Reporte";
SELECT email, COUNT(*) as cantidad FROM usuario 
WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*) > 1;

-- 3. PROGRAMAS DUPLICADOS (mismo cod)
SELECT '--- PROGRAMAS DUPLICADOS POR COD ---' as "Reporte";
SELECT cod, COUNT(*) as cantidad FROM programa GROUP BY cod HAVING COUNT(*) > 1;

-- 4. PROGRAMAS DUPLICADOS (mismo cod + institucion)
SELECT '--- PROGRAMAS DUPLICADOS (COD + INST) ---' as "Reporte";
SELECT cod, inst, COUNT(*) as cantidad FROM programa 
GROUP BY cod, inst HAVING COUNT(*) > 1;

-- 5. ASIGNATURAS DUPLICADAS (programa + codigo + plan)
SELECT '--- ASIGNATURAS DUPLICADAS (PROG + COD + PLAN) ---' as "Reporte";
SELECT programa, codigo, plan, COUNT(*) as cantidad FROM asignatura 
GROUP BY programa, codigo, plan HAVING COUNT(*) > 1;

-- 6. ASIGNATURAS CON COD DUPLICADO
SELECT '--- ASIGNATURAS CON COD DUPLICADO ---' as "Reporte";
SELECT cod, COUNT(*) as cantidad FROM asignatura GROUP BY cod HAVING COUNT(*) > 1;

-- 7. INSTITUCIONES DUPLICADAS POR NOMBRE
SELECT '--- INSTITUCIONES CON NOMBRE DUPLICADO ---' as "Reporte";
SELECT nombre, COUNT(*) as cantidad FROM institucion GROUP BY nombre HAVING COUNT(*) > 1;

-- 8. INSTITUCIONES DUPLICADAS POR ABREV
SELECT '--- INSTITUCIONES CON ABREV DUPLICADA ---' as "Reporte";
SELECT abrev, COUNT(*) as cantidad FROM institucion 
WHERE abrev IS NOT NULL GROUP BY abrev HAVING COUNT(*) > 1;

-- 9. EQUIVALENCIAS DUPLICADAS (asg_pca + asg_ext)
SELECT '--- EQUIVALENCIAS DUPLICADAS ---' as "Reporte";
SELECT asg_pca, asg_ext, COUNT(*) as cantidad FROM equiv 
GROUP BY asg_pca, asg_ext HAVING COUNT(*) > 1;

-- 10. PLANES DUPLICADOS (programa + num)
SELECT '--- PLANES DUPLICADOS (PROG + NUM) ---' as "Reporte";
SELECT programa, num, COUNT(*) as cantidad FROM plan 
GROUP BY programa, num HAVING COUNT(*) > 1;

-- 11. HOMOLOGACIÓN DETALLES DUPLICADOS
SELECT '--- HOMOLOGACIÓN DETALLES DUPLICADOS ---' as "Reporte";
SELECT homologacion_id, asignatura_pca_cod, COUNT(*) as cantidad FROM homologacion_detalle 
GROUP BY homologacion_id, asignatura_pca_cod HAVING COUNT(*) > 1;

-- =====================================================
-- INTEGRIDAD REFERENCIAL
-- =====================================================

-- 12. HOMOLOGACIONES CON USUARIO INVÁLIDO
SELECT '--- HOMOLOGACIONES CON USUARIO INVÁLIDO ---' as "Reporte";
SELECT h.id, h.user_id FROM homologacion h
LEFT JOIN usuario u ON u.id = h.user_id
WHERE u.id IS NULL;

-- 13. HOMOLOGACIONES CON PROGRAMA PCA INVÁLIDO
SELECT '--- HOMOLOGACIONES CON PROGRAMA PCA INVÁLIDO ---' as "Reporte";
SELECT h.id, h.programa_pca_cod FROM homologacion h
LEFT JOIN programa p ON p.cod = h.programa_pca_cod
WHERE p.cod IS NULL;

-- 14. HOMOLOGACIONES CON INSTITUCIÓN INVÁLIDA
SELECT '--- HOMOLOGACIONES CON INSTITUCIÓN INVÁLIDA ---' as "Reporte";
SELECT h.id, h.institucion_id FROM homologacion h
LEFT JOIN institucion i ON i.id = h.institucion_id
WHERE h.institucion_id IS NOT NULL AND i.id IS NULL;

-- 15. HOMOLOGACIÓN DETALLES INVÁLIDOS
SELECT '--- HOMOLOGACIÓN DETALLES CON HOMOLOG INVÁLIDA ---' as "Reporte";
SELECT hd.id, hd.homologacion_id FROM homologacion_detalle hd
LEFT JOIN homologacion h ON h.id = hd.homologacion_id
WHERE h.id IS NULL;

-- 16. ASIGNATURAS CON PROGRAMA INVÁLIDO
SELECT '--- ASIGNATURAS CON PROGRAMA INVÁLIDO ---' as "Reporte";
SELECT a.id, a.programa FROM asignatura a
LEFT JOIN programa p ON p.cod = a.programa
WHERE a.programa IS NOT NULL AND p.cod IS NULL;

-- 17. ASIGNATURAS CON PLAN INVÁLIDO
SELECT '--- ASIGNATURAS CON PLAN INVÁLIDO ---' as "Reporte";
SELECT a.id, a.plan FROM asignatura a
LEFT JOIN plan pl ON pl.id = a.plan
WHERE a.plan IS NOT NULL AND pl.id IS NULL;

-- 18. EQUIVALENCIAS CON ASIGNATURA INVÁLIDA
SELECT '--- EQUIVALENCIAS CON ASIGNATURA INVÁLIDA ---' as "Reporte";
SELECT e.id, e.asg_pca FROM equiv e
LEFT JOIN asignatura a ON a.cod = e.asg_pca
WHERE a.cod IS NULL
UNION ALL
SELECT e.id, e.asg_ext FROM equiv e
LEFT JOIN asignatura a ON a.cod = e.asg_ext
WHERE a.cod IS NULL;

-- =====================================================
-- VALIDACIONES DE DATOS
-- =====================================================

-- 19. USUARIOS CON CLAVE VACÍA
SELECT '--- USUARIOS CON CLAVE VACÍA ---' as "Reporte";
SELECT id, clave FROM usuario WHERE TRIM(clave) = '';

-- 20. ASIGNATURAS CON CRÉDITOS NEGATIVOS
SELECT '--- ASIGNATURAS CON CRÉDITOS NEGATIVOS ---' as "Reporte";
SELECT id, nombre, creditos FROM asignatura WHERE creditos < 0;

-- 21. REGISTROS CON TIMESTAMPS FUTUROS
SELECT '--- REGISTROS CON TIMESTAMPS FUTUROS ---' as "Reporte";
SELECT 'usuario' as tabla, COUNT(*) as cantidad FROM usuario WHERE created_at > NOW()
UNION ALL
SELECT 'programa', COUNT(*) FROM programa WHERE created_at > NOW()
UNION ALL
SELECT 'asignatura', COUNT(*) FROM asignatura WHERE created_at > NOW()
UNION ALL
SELECT 'homologacion', COUNT(*) FROM homologacion WHERE created_at > NOW();

-- =====================================================
-- RESUMEN GENERAL
-- =====================================================

SELECT '=== RESUMEN DE VALIDACIÓN ===' as "Reporte";
SELECT 
    'usuario' as tabla,
    COUNT(*) as total,
    COUNT(DISTINCT id) as unicos
FROM usuario
UNION ALL
SELECT 'programa', COUNT(*), COUNT(DISTINCT cod) FROM programa
UNION ALL
SELECT 'asignatura', COUNT(*), COUNT(DISTINCT (CONCAT(programa, codigo, plan))) FROM asignatura
UNION ALL
SELECT 'institucion', COUNT(*), COUNT(DISTINCT nombre) FROM institucion
UNION ALL
SELECT 'equiv', COUNT(*), COUNT(DISTINCT (CONCAT(asg_pca, asg_ext))) FROM equiv
UNION ALL
SELECT 'homologacion', COUNT(*), COUNT(*) FROM homologacion
UNION ALL
SELECT 'homologacion_detalle', COUNT(*), COUNT(DISTINCT (CONCAT(homologacion_id, asignatura_pca_cod))) FROM homologacion_detalle;
