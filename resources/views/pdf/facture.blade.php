<!-- resources/views/pdf/facture.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            padding: 20px 0;
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .logo {
            float: left;
            max-width: 150px;
        }
        .school-info {
            float: right;
            text-align: right;
            font-size: 10pt;
        }
        .school-name {
            font-size: 20pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .document-title {
            text-align: center;
            font-size: 24pt;
            font-weight: bold;
            margin: 20px 0;
            color: #333;
        }
        .facture-info {
            background-color: #f5f5f5;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px;
        }
        .info-table .label {
            font-weight: bold;
            width: 30%;
        }
        .client-info {
            float: left;
            width: 60%;
        }
        .facture-details {
            float: right;
            width: 35%;
            text-align: right;
        }
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            width: 35%;
            margin-left: 65%;
        }
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 5px;
        }
        .total-table .total-label {
            font-weight: bold;
        }
        .grand-total {
            font-size: 14pt;
            font-weight: bold;
            background-color: #f5f5f5;
            border-top: 2px solid #333;
        }
        .payment-info {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .notes {
            margin-top: 30px;
            font-size: 10pt;
        }
        .footer {
            margin-top: 50px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            font-size: 9pt;
            text-align: center;
        }
        .status-label {
            display: inline-block;
            padding: 5px 10px;
            color: white;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
            font-size: 10pt;
        }
        .status-payee {
            background-color: #28a745;
        }
        .status-partiellement {
            background-color: #ffc107;
            color: #333;
        }
        .status-impayee {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                @if(isset($ecole) && $ecole->logo)
                    <img src="{{ public_path($ecole->logo) }}" alt="Logo de l'école">
                @endif
                <div class="school-name">ÉTABLISSEMENT SCOLAIRE</div>
            </div>
            <div class="school-info">
                Adresse de l'école<br>
                Téléphone: +xxx xxx xxx<br>
                Email: contact@ecole.com<br>
                Site web: www.ecole.com
            </div>
        </div>
        
        <div class="document-title">FACTURE</div>
        
        <div class="facture-info clearfix">
            <div class="client-info">
                <strong>Facturé à:</strong><br>
                {{ $facture->inscription->eleve->nom }} {{ $facture->inscription->eleve->prenom }}<br>
                Matricule: {{ $facture->inscription->eleve->matricule }}<br>
                Classe: {{ $facture->inscription->classe->nom }} ({{ $facture->inscription->classe->niveau->libelle }})<br>
                @if($facture->inscription->eleve->adresse)
                    Adresse: {{ $facture->inscription->eleve->adresse }}<br>
                @endif
                Parent: {{ $facture->inscription->eleve->nom_parent }}<br>
                Tél: {{ $facture->inscription->eleve->contact_parent }}
            </div>
            <div class="facture-details">
                <table class="info-table">
                    <tr>
                        <td class="label">N° de facture:</td>
                        <td>{{ $facture->numero }}</td>
                    </tr>
                    <tr>
                        <td class="label">Date d'émission:</td>
                        <td>{{ date('d/m/Y', strtotime($facture->date_emission)) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Année scolaire:</td>
                        <td>{{ $facture->inscription->anneeScolaire->libelle }}</td>
                    </tr>
                    <tr>
                        <td class="label">Statut:</td>
                        <td>
                            @if($facture->statut == 'payée')
                                <span class="status-label status-payee">Payée</span>
                            @elseif($facture->statut == 'partiellement_payée')
                                <span class="status-label status-partiellement">Partiellement payée</span>
                            @else
                                <span class="status-label status-impayee">Impayée</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%">N°</th>
                    <th width="45%">Désignation</th>
                    <th width="15%">Qté</th>
                    <th width="15%">Prix unitaire</th>
                    <th width="20%">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Frais de scolarité - {{ $facture->inscription->classe->niveau->libelle }}</td>
                    <td>1</td>
                    <td class="text-right">{{ number_format($facture->montant_total, 0, ',', ' ') }} FCFA</td>
                    <td class="text-right">{{ number_format($facture->montant_total, 0, ',', ' ') }} FCFA</td>
                </tr>
                <!-- Si les détails de facture étaient disponibles, ils seraient ajoutés ici -->
                <!-- Lignes vides pour l'esthétique -->
                <tr>
                    <td colspan="5">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="5">&nbsp;</td>
                </tr>
            </tbody>
        </table>
        
        <div class="total-section">
            <table class="total-table">
                <tr>
                    <td class="total-label">Montant total:</td>
                    <td class="text-right">{{ number_format($facture->montant_total, 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <td class="total-label">Montant payé:</td>
                    <td class="text-right">{{ number_format($facture->montant_paye ?? 0, 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <td class="total-label">Montant restant:</td>
                    <td class="text-right">{{ number_format($facture->montant_restant ?? $facture->montant_total, 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr class="grand-total">
                    <td class="total-label">TOTAL À PAYER:</td>
                    <td class="text-right">{{ number_format($facture->montant_restant ?? $facture->montant_total, 0, ',', ' ') }} FCFA</td>
                </tr>
            </table>
        </div>
        
        <div class="payment-info">
            <h3>Modalités de paiement</h3>
            <p>Veuillez effectuer le paiement dans un délai de 30 jours à compter de la date d'émission de la facture.</p>
            <p><strong>Modes de paiement acceptés:</strong> Espèces, Chèque, Virement bancaire</p>
            <p><strong>Coordonnées bancaires:</strong> Banque XYZ - IBAN: XX00 0000 0000 0000 0000 0000</p>
        </div>
        
        <div class="notes">
            <p><strong>Notes:</strong></p>
            <p>Le paiement complet est requis pour la participation aux examens.</p>
            <p>Merci de votre confiance.</p>
        </div>
        
        <div class="footer">
            <p>Document généré automatiquement le {{ date('d/m/Y') }}</p>
            <p>Établissement Scolaire - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>