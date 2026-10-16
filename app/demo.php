<?php
declare(strict_types=1);
/** Fictional, in-memory data. Never connects to or seeds the application database. */
function demo_reports(): array
{
    $items = [
        ['Fuite sur la canalisation principale', 'Bâtiment A · Sous-sol', 'Plomberie', 'Urgente', 'À traiter', 'Critique'],
        ['Remplacement du robinet mélangeur', 'Bâtiment B · Sanitaires RDC', 'Sanitaire', 'Moyenne', 'En cours', 'Moyen'],
        ['Maintenance de la pompe de forage', 'Zone technique · Forage n° 1', 'Forage', 'Urgente', 'En cours', 'Critique'],
        ['Contrôle des chasses d’eau', 'Bâtiment A · 1er étage', 'Sanitaire', 'Faible', 'Résolue', 'À suivre'],
        ['Baisse de pression du réseau', 'Bâtiment C · Local technique', 'Plomberie', 'Moyenne', 'À traiter', 'Moyen'],
        ['Nettoyage du réservoir de stockage', 'Zone technique · Réservoir', 'Forage', 'Faible', 'Résolue', 'À suivre'],
        ['Joint d’étanchéité à remplacer', 'Bâtiment B · 2e étage', 'Sanitaire', 'Moyenne', 'À traiter', 'Moyen'],
        ['Inspection du réseau d’évacuation', 'Bâtiment A · Extérieur', 'Plomberie', 'Faible', 'Résolue', 'À suivre'],
        ['Vérification du compteur d’eau', 'Entrée principale', 'Plomberie', 'Faible', 'Résolue', 'À suivre'],
        ['Entretien du surpresseur', 'Zone technique · Forage n° 2', 'Forage', 'Moyenne', 'En cours', 'Moyen'],
        ['Débouchage du lavabo', 'Bâtiment C · Sanitaires', 'Sanitaire', 'Moyenne', 'Résolue', 'Moyen'],
        ['Contrôle de la qualité de l’eau', 'Zone technique · Forage n° 1', 'Forage', 'Faible', 'Résolue', 'À suivre'],
    ];
    $rows = [];
    foreach ($items as $i => [$title, $location, $category, $priority, $status, $condition]) {
        $rows[] = ['id' => 128 - $i, 'title' => $title, 'location' => $location, 'category' => $category, 'priority' => $priority, 'status' => $status, 'condition' => $condition, 'part' => $category === 'Plomberie' ? 'Joint et raccord de canalisation' : 'À préciser après diagnostic', 'notes' => 'Anomalie relevée lors de la visite de contrôle. Prévoir une vérification sur site et consigner le compte rendu de l’intervention.', 'owner_id' => 1, 'owner_name' => 'Équipe maintenance', 'created_at' => date('Y-m-d H:i:s', strtotime("-$i days 09:30")), 'updated_at' => date('Y-m-d H:i:s', strtotime("-$i days 11:15")), 'version' => 1, 'archived' => 0];
    }
    // Past, resolved interventions make the six-month overview representative.
    for ($i = 0; $i < 30; $i++) {
        $row = $rows[$i % 12];
        $row['id'] = 116 - $i;
        $row['status'] = $i % 7 === 0 ? 'En cours' : 'Résolue';
        $row['created_at'] = date('Y-m-d H:i:s', strtotime('-' . (18 + $i * 5) . ' days'));
        $row['updated_at'] = $row['created_at'];
        $rows[] = $row;
    }
    return $rows;
}
