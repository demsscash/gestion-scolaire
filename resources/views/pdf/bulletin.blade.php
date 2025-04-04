<!-- resources/views/pdf/bulletin.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de notes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .school-info {
            font-size: 12px;
            margin-bottom: 10px;
        }
        .bulletin-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
            text-transform: uppercase;
        }
        .session-info {
            font-size: 14px;
            margin-bottom: 15px;
        }
        .student-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .student-info-left, .student-info-right {
            width: 48%;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th {
            background-color: #eee;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
        }
        .notes-table th {
            text-align: center;
        }
        .matiere-row {
            background-color: #f9f9f9;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .summary-left, .summary-right {
            width: 48%;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .decision {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature-box {
            text-align: center;
            width: 30%;
        }
        .signature-line {
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 40px;
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
            <div class="bulletin-title">Bulletin de Notes</div>
            <div class="session-info">
                {{ $bulletin->session->libelle }} - Année scolaire {{ $bulletin->session->anneeScolaire->libelle }}
            </div>
        </div>
        
        <div class="student-info">
            <div class="student-info-left">
                <p><span class="info-label">Élève:</span> {{ $bulletin->inscription->eleve->prenom }} {{ $bulletin->inscription->eleve->nom }}</p>
                <p><span class="info-label">Matricule:</span> {{ $bulletin->inscription->eleve->matricule }}</p>
                <p><span class="info-label">Date de naissance:</span> {{ $bulletin->inscription->eleve->date_naissance->format('d/m/Y') }}</p>
            </div>
            <div class="student-info-right">
                <p><span class="info-label">Classe:</span> {{ $bulletin->inscription->classe->nom }}</p>
                <p><span class="info-label">Niveau:</span> {{ $bulletin->inscription->classe->niveau->libelle }}</p>
                <p><span class="info-label">Effectif:</span> {{ $bulletin->inscription->classe->inscriptions()->where('statut', 'actif')->count() }} élèves</p>
            </div>
        </div>
        
        <table class="notes-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30%;">Matière</th>
                    <th rowspan="2" style="width: 10%;">Coef</th>
                    <th rowspan="2" style="width: 10%;">Note</th>
                    <th colspan="3">Classe</th>
                    <th rowspan="2" style="width: 30%;">Appréciation</th>
                </tr>
                <tr>
                    <th style="width: 10%;">Moy</th>
                    <th style="width: 5%;">Min</th>
                    <th style="width: 5%;">Max</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalCoef = 0;
                    $totalPoints = 0;
                @endphp
                
                @foreach($notes as $note)
                    @php
                        $totalCoef += $note->matiereNiveau->coefficient;
                        $totalPoints += $note->valeur * $note->matiereNiveau->coefficient;
                        $statMatiere = $stats_matieres[$note->matiereNiveau->id] ?? null;
                    @endphp
                    <tr class="matiere-row">
                        <td>{{ $note->matiereNiveau->matiere->libelle }}</td>
                        <td style="text-align: center;">{{ $note->matiereNiveau->coefficient }}</td>
                        <td style="text-align: center;">{{ number_format($note->valeur, 2) }}</td>
                        <td style="text-align: center;">{{ $statMatiere ? number_format($statMatiere['moyenne_classe'], 2) : '-' }}</td>
                        <td style="text-align: center;">{{ $statMatiere ? number_format($statMatiere['note_min'], 2) : '-' }}</td>
                        <td style="text-align: center;">{{ $statMatiere ? number_format($statMatiere['note_max'], 2) : '-' }}</td>
                        <td>{{ $note->appreciation }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th style="text-align: center;">{{ $totalCoef }}</th>
                    <th colspan="5"></th>
                </tr>
            </tfoot>
        </table>
        
        <div class="summary">
            <div class="summary-left">
                <p><span class="info-label">Moyenne générale:</span> <strong>{{ number_format($bulletin->moyenne_generale, 2) }}/20</strong></p>
                <p><span class="info-label">Rang:</span> <strong>{{ $bulletin->rang }}/{{ $bulletin->inscription->classe->inscriptions()->where('statut', 'actif')->count() }}</strong></p>
                <p><span class="info-label">Moyenne de classe:</span> {{ isset($moyenne_classe) ? number_format($moyenne_classe, 2) : '-' }}/20</p>
            </div>
            <div class="summary-right">
                <p><strong>Appréciation générale:</strong></p>
                <p>{{ $bulletin->appreciation_generale ?? 'Aucune appréciation fournie.' }}</p>
            </div>
        </div>
        
        <div class="decision">
            <p><strong>Décision du conseil de classe:</strong> 
                @if($bulletin->decision == 'passage')
                    Admis(e) en classe supérieure
                @elseif($bulletin->decision == 'redoublement')
                    Redoublement
                @else
                    En attente de décision
                @endif
            </p>
        </div>
        
        <div class="signature">
            <div class="signature-box">
                <div class="signature-line">Titulaire de classe</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Parent/Tuteur</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Directeur</div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; font-size: 10px;">
            <p>Document généré le {{ date('d/m/Y') }} - École</p>
        </div>
    </div>
</body>
</html>