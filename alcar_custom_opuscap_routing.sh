#!/bin/bash

# Siirretään tiliotteet Örumin Pupesoftista Alcarin Pupesoftiin
ssh devlab@193.185.248.50 'if [ "$(ls -A --ignore=done /home/opuscap/in_alcar/)" ]; then scp -q /home/opuscap/in_alcar/titoartr* devlab@172.16.1.32:/home/opuscap/in/ ; mv -f /home/opuscap/in_alcar/titomergr* /home/opuscap/in_alcar/done/ ; fi';

# Siirretään viiteaineistot Örumin Pupesoftista Alcarin Pupesoftiin
ssh devlab@193.185.248.50 'if [ "$(ls -A --ignore=done /home/opuscap/in_alcar/)" ]; then scp -q /home/opuscap/in_alcar/viiteartr* devlab@172.16.1.32:/home/opuscap/in/ ; mv -f /home/opuscap/in_alcar/viitemergr* /home/opuscap/in_alcar/done/ ; fi';

# Siirretään sisviiteaineistot Örumin Pupesoftista Alcarin Pupesoftiin
ssh devlab@193.185.248.50 'if [ "$(ls -A --ignore=done /home/opuscap/in_alcar/)" ]; then scp -q /home/opuscap/in_alcar/sisviiteartr* devlab@172.16.1.32:/home/opuscap/in/ ; mv -f /home/opuscap/in_alcar/sisviitemergr* /home/opuscap/in_alcar/done/ ; fi';
