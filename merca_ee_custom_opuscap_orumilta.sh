#!/bin/bash

# Haetaan maksuaineistot Viron koneelta örumin koneelle siihen saakka näin, kun Örumn saa Viron reititykset kuntoon
scp -q devlab@192.168.131.70:/home/opuscap/out_ee/*SEPA-* /home/opuscap/out_ee/. ; chmod 777 /home/opuscap/out_ee/*SEPA-* ;
ssh devlab@192.168.131.70 'mv -f /home/opuscap/out_ee/*SEPA-*.xml /home/opuscap/out_ee/done/' ; 