<!-- resources/views/pdf/bulletins_classe.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletins de la classe {{ $classe->nom }}</title>
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
        .classe-info {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .session-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .page-break {
            page-break-after: always;
        }
        .bullet-icon {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #333;
            border-radius: 50%;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bulletins de Notes</h1>
        </div>
        
        <div class="classe-info">
            Classe: {{ $classe->nom }} - {{ $classe->niveau->libelle }}
        </div>
        
        <div class="session-info">
            {{ $session->libelle }} - Année scolaire {{ $session->anneeScolaire->libelle }}
        </div>
        
        <div>
            <p>Effectif de la classe: {{ $bulletins->count() }} élèves</p>
            <p>Moyenne générale de la classe: {{ number_format($bulletins->avg('moyenne_generale'), 2) }}/20</p>
            <p>Date d'édition: {{ date('d/m/Y') }}</p>
        </div>
        
        <!-- Table des matières -->
        <div style="margin-top: 30px; margin-bottom: 30px;">
            <h3>Liste des élèves:</h3>
            <ol>
                @foreach($bulletins as $index => $bulletin)
                    <li>
                        <a href="#eleve-{{ $bulletin->inscription->eleve->id }}">
                            {{ $bulletin->inscription->eleve->nom }} {{ $bulletin->inscription->eleve->prenom }}
                        </a>
                        - Moyenne: {{ number_format($bulletin->moyenne_generale, 2) }}/20
                        - Rang: {{ $bulletin->rang }}/{{ $bulletins->count() }}
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <!-- Inclure chaque bulletin individuel -->
    @foreach($bulletins as $bulletin)
        <div id="eleve-{{ $bulletin->inscription->eleve->id }}">
            @include('pdf.bulletin_fragment', ['bulletin' => $bulletin])
        </div>
        
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>