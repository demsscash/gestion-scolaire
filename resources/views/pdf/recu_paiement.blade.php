<!-- resources/views/pdf/recu_paiement.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement</title>
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
        .recu-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            width: 40%;
        }
        .info-value {
            width: 60%;
        }
        .student-info {
            margin-bottom: 30px;
        }
        .montant-box {
            border: 2px solid #333;
            padding: 10px;
            text-align: center;
            margin: 20px 0;
        }
        .montant {
            font-size: 16pt;
            font-weight: bold;
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
        
        <div class="title">Reçu de Paiement</div>
        
        <div class="recu-info">
            <div class="info-row">
                <span class="info-label">Numéro de reçu:</span>
                <span class="info-value">RECU-{{ $paiement->id }}-{{ date('Ymd', strtotime($paiement->date_paiement)) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date de paiement:</span>
                <span class="info-value">{{ date('d/m/Y', strtotime($paiement->date_paiement)) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Mode de paiement:</span>
                <span class="info-value">{{ ucfirst($paiement->mode_paiement) }}</span>
            </div>
            @if($paiement->reference)
            <div class="info-row">
                <span class="info-label">Référence:</span>
                <span class="info-value">{{ $paiement->reference }}</span>
            </div>
            @endif
        </div>
        
        <div class="student-info">
            <div class="info-row">
                <span class="info-label">Élève:</span>
                <span class="info-value">{{ $paiement->inscription->eleve->prenom }} {{ $paiement->inscription->eleve->nom }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Matricule:</span>
                <span class="info-value">{{ $paiement->inscription->eleve->matricule }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Classe:</span>
                <span class="info-value">{{ $paiement->inscription->classe->nom }} ({{ $paiement->inscription->classe->niveau->libelle }})</span>
            </div>
            <div class="info-row">
                <span class="info-label">Année scolaire:</span>
                <span class="info-value">{{ $paiement->inscription->anneeScolaire->libelle }}</span>
            </div>
            @if($paiement->mois_concerne)
            <div class="info-row">
                <span class="info-label">Mois concerné:</span>
                <span class="info-value">{{ $paiement->mois_concerne }}</span>
            </div>
            @endif
        </div>
        
        <div class="montant-box">
            <div>Montant payé:</div>
            <div class="montant">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
        </div>
        
        @if($paiement->description)
        <div>
            <p><strong>Description:</strong></p>
            <p>{{ $paiement->description }}</p>
        </div>
        @endif
        
        <div class="date-signature">
            <p>Fait à ______________, le {{ date('d/m/Y') }}</p>
            <p>Le Caissier / La Caissière</p>
            <div class="signature-line"></div>
        </div>
        
        <div class="reference">
            Réf: PAIE-{{ $paiement->id }}-{{ date('YmdHis') }}
        </div>
        
        <div class="footer">
            <p>Ce reçu est la preuve de votre paiement. Veuillez le conserver soigneusement.</p>
        </div>
    </div>
</body>
</html>