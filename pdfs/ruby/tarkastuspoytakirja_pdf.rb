#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'

class TarkastuspoytakirjaPDF

  def initialize
    margin = 20
    @pdf = Prawn::Document.new(:page_size => "A4", :page_layout => :landscape, :margin => [margin, margin, margin, margin])

    @pdf.font 'Helvetica', :style => :normal, :size => 10

    @pdf.repeat(:all, :dynamic => true) do
      @pdf.draw_text @pdf.page_number, :at => [770, 520]
    end

  end

  def data(data)
    @data = data
  end

  def generate
    if @data.empty?
      #error
      #exit
    end

    @x = 600
    @y = 500
    self.header

    @pdf.move_down 100

    self.company_info

    @pdf.font 'Helvetica', :size => 8
    @x = 200
    @y = 455.28
    customer_data = [
      {
        :header => 'Asiakas nro',
        :value => @data['asiakas']['asiakasnro']
      },
      {
        :header => 'Asiakas',
        :value => @data['asiakas']['nimi']
      },
      {
        :header => 'Katuosoite',
        :value => @data['asiakas']['osoite']
      },
      {
        :header => 'Postiosoite',
        :value => @data['asiakas']['postino'] + ' ' + @data['asiakas']['postitp']
      },
      {
        :header => 'Yhteyshenkilö',
        :value => @data['asiakas']['yhteyshenkilo']
      },
      {
        :header => 'Puh. nro',
        :value => @data['asiakas']['puh']
      },
      {
        :header => 'Tilaus nro',
        :value => @data['tunnus']
      },
    ]
    self.customer_info(customer_data)

    @x += 250
    @y += 105
    spot_data = [
      {
        :header => 'Kust.paikka/merkki',
        :value => '??'
      },
      {
        :header => 'Kohde',
        :value => @data['kohde']['nimi']
      },
      {
        :header => 'Katuosoite',
        :value => @data['kohde']['osoite']
      },
      {
        :header => 'Postiosoite',
        :value => @data['kohde']['postino'] + ' ' + @data['kohde']['postitp']
      },
      {
        :header => 'Yhteyshenkilö',
        :value => @data['kohde']['yhteyshenkilo']
      },
      {
        :header => 'Asiakasvastaava',
        :value => '??'
      },
    ]
    self.spot_info(spot_data)

    @x += 200
    @y += 90
    other_data = [
      {
        :header => 'Pvm',
        :value => 'tähän tämä päivä'
      },
      {
        :header => 'Tilausnumero',
        :value => @data['tunnus']
      },
      {
        :header => 'Puh. nro',
        :value => '??'
      },
      {
        :header => 'Puh. nro',
        :value => '??'
      },
    ]
    self.other_info(other_data)

    @x = @pdf.bounds.left
    @y -= 35
    @pdf.line [@x, @y], [@pdf.bounds.right, @y]

    @pdf.font 'Helvetica', :size => 10
    self.print_device_info

    self.print_signature_table

    filepath = "/tmp/Tarkastuspoytakirja_" + @data['tunnus'].to_s + ".pdf";
    #Filename is needed because we want to return that straigth to HTML form. We only give filename so that we can force the folder downloads are allowed to make.
    filename = "Tarkastuspoytakirja_" + @data['tunnus'].to_s + ".pdf";
    @pdf.render_file filepath

    return filename
  end

  def company_info
    @pdf.text @data['yhtio']['nimi']
    @pdf.move_down 10
    @pdf.text @data['yhtio']['osoite']
    @pdf.move_down 10
    @pdf.text @data['yhtio']['postino'] + ' ' + @data['yhtio']['postitp']
    @pdf.move_down 5
    @pdf.text @data['yhtio']['puhelin']
  end

  def customer_info(customer_data)
    customer_data.each do |value|
      @pdf.draw_text value[:header], :style => :bold, :at => [@x, @y]
      @pdf.draw_text value[:value], :style => :normal, :at => [@x+100, @y]

      @y -= 15
    end
  end

  def spot_info(spot_data)
    spot_data.each do |value|
      @pdf.draw_text value[:header], :style => :bold, :at => [@x, @y]
      @pdf.draw_text value[:value], :style => :normal, :at => [@x+100, @y]

      @y -= 15
    end
  end

  def other_info(other_data)
    other_data.each do |value|
      @pdf.draw_text value[:header], :style => :bold, :at => [@x, @y]
      @pdf.draw_text value[:value], :style => :normal, :at => [@x+100, @y]

      @y -= 15
    end
  end

  def print_device_info
    self.print_row_headers

    @y -= 50
    self.print_rows(@data['rivit'])
  end

  def print_row_headers
    #otetaan tässä vaiheessa y talteen, jotta sitä voidaan käyttää pystyviivan tekemiseen
    y_temp = @y

    @x = 10
    @y -= 10
    @pdf.draw_text 'Laitetiedot', :at => [@x, @y]

    @pdf.draw_text 'Tehdyt toimenpiteet', :at => [@x+650, @y]

    @pdf.stroke_horizontal_line 1, 1, :at => @y

    @y = @y - 48
    @pdf.font 'Helvetica', :size => 8
    @pdf.draw_text "Sijainti nro", :at => [@x, @y], :rotate => 90

    @x += 30
    @pdf.draw_text "Laitteen sijainti", :at => [@x, @y]

    @x += 170
    @pdf.draw_text "Muuttunut", :at => [@x, @y], :rotate => 90
    @x += 10
    @pdf.draw_text "sijainti", :at => [@x, @y], :rotate => 90

    @x += 20
    @pdf.draw_text "Merkki / malli", :at => [@x, @y]

    @x += 100
    @y += 10
    @pdf.draw_text "Koko", :at => [@x, @y]
    @y -= 10
    @pdf.draw_text "kg / litra", :at => [@x, @y]

    @x += 50
    @y += 10
    @pdf.draw_text "Palo-/", :at => [@x, @y]
    @y -= 10
    @pdf.draw_text "teholuokka", :at => [@x, @y]

    @x += 50
    @pdf.draw_text "Sammute", :at => [@x, @y]

    @x += 50
    @y += 10
    @pdf.draw_text "Säiliön", :at => [@x, @y]
    @y -= 10
    @pdf.draw_text "nro", :at => [@x, @y]

    @x += 70
    @y += 10
    @pdf.draw_text "Ponnep", :at => [@x, @y]
    @y -= 10
    @pdf.draw_text "nro", :at => [@x, @y]

    @x += 50
    @pdf.draw_text "Poikkeama", :at => [@x, @y], :rotate => 90
    @x += 10
    @pdf.draw_text "raportti", :at => [@x, @y], :rotate => 90

    @x += 20
    @pdf.draw_text "Viimeinen", :at => [@x, @y], :rotate => 90
    @x += 10
    @pdf.draw_text "painekoe", :at => [@x, @y], :rotate => 90

    @x += 5
    @pdf.line [@x, y_temp], [@x, 0]

    @x += 20
    @pdf.draw_text "Tark. väli", :at => [@x, @y], :rotate => 90

    @x += 30
    @pdf.draw_text "Tarkastus", :at => [@x, @y], :rotate => 90

    @x += 30
    @pdf.draw_text "Huolto", :at => [@x, @y], :rotate => 90

    @x += 30
    @pdf.draw_text "Painekoe", :at => [@x, @y], :rotate => 90

    #TODO WTF happens here why need stroke_hori....
    @pdf.line [@pdf.bounds.left, @y-5], [@pdf.bounds.right, @y-5]
    @pdf.stroke_horizontal_line 1, 1, :at => @y-20
  end

  def print_rows(rows)
    @x = 10
    rows.each do |row|
      @pdf.draw_text row['laite']['oma_numero'], :at => [@x, @y]

      #TODO if the length of this field goes over the next value put it in two lines
      @x += 30
      @pdf.draw_text row['laite']['sijainti'], :at => [@x, @y]

      #muuttunut sijainti
      @x += 160
      @pdf.line [@x, @y], [@x+30, @y]

      @x += 40
      @pdf.draw_text row['laite']['tuoteno'], :at => [@x, @y]

      #TODO tuotteen avainsanoista koko
      @x += 100
      @pdf.draw_text "", :at => [@x, @y]

      #TODO palo teho luokka??
      @x += 50
      @pdf.draw_text "", :at => [@x, @y]

      #TODO sammute??
      @x += 50
      @pdf.draw_text "", :at => [@x, @y]

      @x += 50
      @pdf.draw_text row['laite']['sarjanro'], :at => [@x, @y]

      #TODO ponnop nro??
      @x += 70
      @pdf.draw_text "", :at => [@x, @y]

      #TODO poikkeama raportti??
      @x += 50
      @pdf.draw_text "", :at => [@x, @y]

      #TODO viimeinen painekoe
      @x += 20
      @pdf.draw_text "", :at => [@x, @y]

      @x += 5

      #TODO huoltosykleistä tarkastus väli
      @x += 30
      @pdf.draw_text "", :at => [@x, @y]

      #TODO oliko kyseessä tarkastus ja sen tuoteno
      @x += 30
      @pdf.draw_text "", :at => [@x, @y]

      #TODO oliko kyseessä huolto ja sen tuoteno
      @x += 30
      @pdf.draw_text "", :at => [@x, @y]

      #TODO oliko kyseessä painekoe ja sen tuoteno
      @x += 30
      @pdf.draw_text "", :at => [@x, @y]

      @x = 10
      @y -= 20
    end
  end

  def print_signature_table
    #this method has fixed position. it is allways at the bottom of the page
    @x = 0
    @y = 50

    @pdf.line [@pdf.bounds.left, @y], [@pdf.bounds.right, @y]
    @pdf.stroke_horizontal_line 1, 1, :at => @y

    @y -= 10
    @pdf.draw_text "Pvm", :at => [@x, @y]

    @x += 120
    @pdf.line [@x-5, 50], [@x-5, 0]
    @pdf.draw_text "Työn suorittajan kuittaus / nimen selvennys", :at => [@x, @y]


    @x += 300
    @pdf.line [@x-5, 50], [@x-5, 0]
    @pdf.draw_text "Asiakkaan kuittaus / nimen selvennys", :at => [@x, @y]

    @y = 0
    @pdf.line [@pdf.bounds.left, @y], [@pdf.bounds.right, @y]
    @pdf.stroke_horizontal_line 1, 1, :at => @y

  end

  def header
    self.logo
    self.tarkastuspoytakirja_header
  end

  def logo
    #@pdf.image "/Users/joonas/Dropbox/Devlab yleiset/Projektit/Turvata/safetyeasy dokumentaatiot/Raporttimallit/turvanasi_logo.png", :scale => 0.7, :at => [0, 540]
    @pdf.image File.dirname(__FILE__) + '/../../pics/turvanasi_logo.png', :scale => 0.7, :at => [0, 540]
  end

  def tarkastuspoytakirja_header
    @pdf.font 'Helvetica', :style => :bold
    @pdf.draw_text "Tarkastuspöytäkirjan nro 1848", :at => [@x, @y]
    @pdf.font 'Helvetica', :style => :normal
  end
end

class WorkOrderDAO

  attr_accessor :data

  def initialize(filepath)
    self.fetch_data(filepath)
  end

  def fetch_data(filepath)
    @data = JSON.load(File.read(filepath))
  end

  def data
    return @data
  end
end

if !ARGV[0].empty?

  workorder = WorkOrderDAO.new(ARGV[0])

  pdf = TarkastuspoytakirjaPDF.new
  pdf.data(workorder.data)

  puts pdf.generate
else
  #error
  #exit
end
