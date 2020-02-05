#!/bin/bash

# Siirretään tiliotteet örskan koneelta tänne
ssh devlab@193.185.248.50 'if [ "$(ls -A --ignore=done /home/opuscap/in_ee/)" ]; then scp -q /home/opuscap/in_ee/titomergr* devlab@192.168.131.70:/home/opuscap/in_ee/ ; mv -f /home/opuscap/in_ee/titomergr* /home/opuscap/in_ee/done/ ; fi';

# Siirretään viiteaineistot örskan koneelta tänne
ssh devlab@193.185.248.50 'if [ "$(ls -A --ignore=done /home/opuscap/in_ee/)" ]; then scp -q /home/opuscap/in_ee/viitemergr* devlab@192.168.131.70:/home/opuscap/in_ee/ ; mv -f /home/opuscap/in_ee/viitemergr* /home/opuscap/in_ee/done/ ; fi';

# Siirretään sisviiteaineistot örskan koneelta tänne
ssh devlab@193.185.248.50 'if [ "$(ls -A --ignore=done /home/opuscap/in_ee/)" ]; then scp -q /home/opuscap/in_ee/sisviitemergr* devlab@192.168.131.70:/home/opuscap/in_ee/ ; mv -f /home/opuscap/in_ee/sisviitemergr* /home/opuscap/in_ee/done/ ; fi';

# Tämä kommentoitu toistaiseksi, koska reititykset eivät vielä kunnossa eikä tämä toimi
# Siirretään maksuaineistot tältä koneelta örumin koneelle
if [ "$(ls -A --ignore=done /home/opuscap/out_ee/)" ]; then scp -q /home/opuscap/out_ee/*SEPA-* devlab@193.185.248.50:/home/opuscap/out_ee/ ; mv -f /home/opuscap/out_ee/*SEPA-* /home/opuscap/out_ee/done/ ; fi