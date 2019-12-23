SupFiles
============

# 4PJT-SupInfo


Credentials Debian-9-4PJT


Utilisateur:
	login:supinfo
	pass:supinfo

Root:
	login:root
	pass:root

Utilisation composer:
	php composer.phar 

Lancer le serveur:
	php bin/console server:run 0.0.0.0:8000

Pour accéder a la page depuis windows:
	http:192.168.130.128:8000

Générer clé ssh:
- ssh-keygen -t rsa -b 4096 -C "your_email@example.com"
- Entrer
- Entrer
- Entrer
- eval $(ssh-agent -s)
- ssh-add ~/.ssh/id_rsa
- cat ~/.ssh/id_rsa (Copier le contenu du fichier)
- Coller le contenu a l'adresse suivante: https://github.com/settings/keys // Bouton vert new SSH KEY

When you want to push:
- git fetch origin # gets you up to date with origin
- git merge origin/master
