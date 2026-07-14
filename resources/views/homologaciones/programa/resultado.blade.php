<!DOCTYPE html>
<html lang="es-ES">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estudio de Homologacion - PCA</title>
    <link rel="icon" href="{{ asset('img/favicon-pca.jpg') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        table { border-collapse: collapse; font-family: Roboto, Arial, sans-serif; font-size: 12px; width: 100%; margin-bottom: 10px; }
        th, td { padding: 4px; }
        table[border="1"] th, table[border="1"] td { border: 1px solid #999; }
        .sombra { background-color: #ccc; }
        .sinborde { border: none; text-align: center; font-size: 10px; }
        body { margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .saiq-pdf-content { background: white; }
        input, textarea { font-family: inherit; }
        textarea { width: 100%; }
        tr, td, th { page-break-inside: avoid; break-inside: avoid; }
        .saiq-ciclo-page { page-break-before: always; break-before: page; margin-top: 30px; }
        .saiq-actions { border: none; text-align: center; padding-top: 20px; }
        @media print {
            body { background: white; margin: 0; }
            .container { box-shadow: none; padding: 0; }
            .noprint { display: none; }
            input, textarea { border: none; }
        }
    </style>
</head>
<body>
<div class="container">
    <div id="saiq-pdf-content" class="saiq-pdf-content">
        @include('homologaciones.programa._documento')

        @if(!empty($cicloUniversitario))
            <div class="saiq-ciclo-page">
                @include('homologaciones.programa._documento', $cicloUniversitario)
            </div>
        @endif
    </div>

    <table border="0" class="noprint" data-html2canvas-ignore="true">
        <tr>
            <td class="saiq-actions">
                <button type="button" onclick="generarPDF()" style="padding: 10px 20px; margin-right: 10px; background: #d32f2f; color: white; border: none; cursor: pointer; border-radius: 4px;">Generar PDF</button>
                <button type="button" onclick="history.back()" style="padding: 10px 20px; background: #757575; color: white; border: none; cursor: pointer; border-radius: 4px;">Volver</button>
            </td>
        </tr>
    </table>
</div>

<script>
function generarPDF() {
    const element = document.getElementById('saiq-pdf-content');
    const nombreArchivo = @js('Homologacion_' . preg_replace('/\s+/', '_', \App\Support\AcademicText::upper($data['ape'] . '_' . $data['nom'])) . '.pdf');
    const opt = {
        margin: [10, 10, 10, 10],
        filename: nombreArchivo,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'], before: '.saiq-ciclo-page', avoid: ['tr'] }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>
