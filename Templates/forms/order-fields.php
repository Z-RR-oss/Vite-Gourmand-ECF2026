<?php // $orderInput contient exclusivement les valeurs à réafficher ; le service recalcule le prix.?>
<label for="adresse_prestation">Adresse de prestation <span class="required-label">(obligatoire)</span></label>
<input type="text" id="adresse_prestation" name="adresse_prestation" maxlength="255" autocomplete="street-address" value="<?= e($orderInput['adresse_prestation'] ?? '') ?>" required>
<label for="lieu_prestation">Ville de prestation <span class="required-label">(obligatoire)</span></label>
<input type="text" id="lieu_prestation" name="lieu_prestation" maxlength="255" autocomplete="address-level2" value="<?= e($orderInput['lieu_prestation'] ?? '') ?>" aria-describedby="delivery-help" required>
<label for="date_prestation">Date de prestation <span class="required-label">(obligatoire)</span></label>
<input type="date" id="date_prestation" name="date_prestation" value="<?= e($orderInput['date_prestation'] ?? '') ?>" required>
<label for="heure_prestation">Heure de prestation <span class="required-label">(obligatoire)</span></label>
<input type="time" id="heure_prestation" name="heure_prestation" value="<?= e(substr($orderInput['heure_prestation'] ?? '', 0, 5)) ?>" required>
<label for="nb_personnes">Nombre de personnes <span class="required-label">(obligatoire)</span></label>
<input type="number" id="nb_personnes" name="nb_personnes" min="<?= (int) $minimumPeople ?>" max="10000" step="1" value="<?= e($orderInput['nb_personnes'] ?? $minimumPeople) ?>" required>
<label for="distance_km">Distance hors Bordeaux en km <span class="required-label">(obligatoire)</span></label>
<p id="delivery-help" class="small-note">À Bordeaux : livraison gratuite, distance ramenée à 0. Dans une autre ville : indiquez une distance positive, à confirmer avec l’équipe. Tarif : 5 € + 0,59 €/km.</p>
<input type="number" id="distance_km" name="distance_km" min="0" max="10000" step="0.1" value="<?= e($orderInput['distance_km'] ?? 0) ?>" aria-describedby="delivery-help" required>
