<!-- resources/views/pdf/attestation_inscription.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attestation d'inscription</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 20pt;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .school-info {
            font-size: 10pt;
            margin-bottom: 10px;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            margin: 30px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .content {
            margin-bottom: 40px;
            text-align: justify;
        }
        .student-info {
            margin-left: 20px;
            margin-bottom: 30px;
        }
        .date-signature {
            text-align: right;
            margin-top: 50px;
        }
        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            width: 200px;
            display: inline-block;
        }
        .footer {
            text-align: center;
            font-size: 9pt;
            margin-top: 50px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .reference {
            font-size: 8pt;
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if(isset($ecole) && $ecole->logo)
                <img src="{{ public_path($ecole->logo) }}" alt="Logo de l'école" class="logo">
            @endif
            <div class="school-name">ÉTABLISSEMENT SCOLAIRE</div>
            <div class="school-info">
                Adresse de l'école<br>
                Téléphone: +xxx xxx xxx<br>
                Email: contact@ecole.com
            </div>
        </div>
        
        <div class="title">Attestation d'inscription</div>
        
        <div class="content">
            <p>Je soussigné(e), <strong>Directeur/Directrice de l'Établissement Scolaire</strong>, certifie que l'élève dont l'identité suit :</p>
            
            <div class="student-info">
                <p><strong>Nom :</strong> {{ $inscription->eleve->nom }}</p>
                <p><strong>Prénom :</strong> {{ $inscription->eleve->prenom }}</p>
                <p><strong>Matricule :</strong> {{ $inscription->eleve->matricule }}</p>
                <p><strong>Date de naissance :</strong> {{ $inscription->eleve->date_naissance->format('d/m/Y') }}</p>
                <p><strong>Lieu de naissance :</strong> {{ $inscription->eleve->lieu_naissance ?? 'Non spécifié' }}</p>
            </div>
            
            <p>est régulièrement inscrit(e) dans notre établissement pour l'année scolaire <strong>{{ $inscription->anneeScolaire->libelle }}</strong> en classe de <strong>{{ $inscription->classe->nom }}</strong> ({{ $inscription->classe->niveau->libelle }}).</p>
            
            <p>La présente attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.</p>
        </div>
        
        <div class="date-signature">
            <p>Fait à ______________, le {{ date('d/m/Y') }}</p>
            <p>Le Directeur / La Directrice</p>
            <div class="signature-line"></div>
        </div>
        
        <div class="reference">
            Réf: INS-{{ $inscription->id }}-{{ date('YmdHis') }}
        </div>
        
        <div class="footer">
            <p>Document officiel - Toute falsification est passible de poursuites judiciaires</p>
        </div>
    </div>
</body>
</html>