#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

class TarkastuspoytakirjaPDF
  @logo          = nil
  @customer_data = nil
  @spot_data     = nil
  @other_data    = nil

  @margin = nil
  @data   = nil

  def initialize
    @margin = 20
  end

  def generate
    init

    #Filename is a separate variable because pdf.render_file wants full path but in HTML save form we want to force the directory user is able to download files from. this is the reason we only retrun filename
    filepath = "/tmp/Tarkastuspoytakirja_#{@data['tunnus'].to_s}.pdf"
    filename = "Tarkastuspoytakirja_#{@data['tunnus'].to_s}.pdf"

    Prawn::Document.generate(filepath,
                             { :page_size   => "A4",
                               :page_layout => :landscape,
                               :margin      => [100, @margin, @margin, @margin]
                             }) do |pdf|
      pdf.font 'Helvetica', :style => :normal, :size => 8
      header pdf

      info pdf

      rows pdf

      footer pdf
    end

    filename
  end

  def init
    @logo   = @data['logo']

    #Space needs to be added if nil. Otherwise text() doesnt work as wanted
    zipcode = @data['asiakas']['postino'].nil? ? ' ' : @data['asiakas']['postino']
    city    = @data['asiakas']['postitp'].nil? ? ' ' : @data['asiakas']['postitp']

    @customer_data = [
        {
            :header => 'Asiakas nro',
            :value  => @data['asiakas']['asiakasnro'].nil? ? ' ' : @data['asiakas']['asiakasnro']
        },
        {
            :header => 'Asiakas',
            :value  => @data['asiakas']['nimi'].nil? ? ' ' : @data['asiakas']['nimi']
        },
        {
            :header => 'Katuosoite',
            :value  => @data['asiakas']['osoite'].nil? ? ' ' : @data['asiakas']['osoite']
        },
        {
            :header => 'Postiosoite',
            :value  => zipcode + ' ' + city
        },
        {
            :header => 'Yhteyshenkilö',
            :value  => @data['asiakas']['yhteyshenkilo'].nil? ? ' ' : @data['asiakas']['yhteyshenkilo']
        },
        {
            :header => 'Puh. nro',
            :value  => @data['asiakas']['puh'].nil? ? ' ' : @data['asiakas']['puh']
        },
        {
            :header => 'Tilaus nro',
            :value  => @data['tunnus'].nil? ? ' ' : @data['asiakas']['tunnus']
        },
    ]

    zipcode = @data['kohde']['postino'].nil? ? ' ' : @data['kohde']['postino']
    city    = @data['kohde']['postitp'].nil? ? ' ' : @data['kohde']['postitp']

    @spot_data = [
        {
            :header => 'Kust.paikka/merkki',
            :value  => ' '
        },
        {
            :header => 'Kohde',
            :value  => @data['kohde']['nimi'].nil? ? ' ' : @data['kohde']['nimi']
        },
        {
            :header => 'Katuosoite',
            :value  => (@data['kohde']['osoite'].nil? or @data['kohde']['osoite'] == '') ? ' ' : @data['kohde']['osoite']
        },
        {
            :header => 'Postiosoite',
            :value  => zipcode + ' ' + city
        },
        {
            :header => 'Yhteyshenkilö',
            :value  => @data['kohde']['yhteyshenkilo'].nil? ? ' ' : @data['kohde']['yhteyshenkilo']
        },
        {
            :header => 'Asiakasvastaava',
            :value  => ' '
        },
    ]

    @other_data = [
        {
            :header => 'Pvm',
            :value  => Time.new.strftime('%d.%m.%Y')
        },
        {
            :header => 'Tilausnumero',
            :value  => @data['tunnus'].nil? ? ' ' : @data['tunnus']
        },
        {
            :header => 'Puh. nro',
            :value  => ' '
        },
        {
            :header => 'Puh. nro',
            :value  => ' '
        },
    ]
  end

  def info(pdf)
    pdf.bounding_box([pdf.bounds.left, pdf.cursor], :width => pdf.bounds.right - @margin, :height => 115) do
      top_coordinate = pdf.cursor
      pdf.font 'Helvetica', :style => :normal, :size => 10

      pdf.text @data['yhtio']['nimi']
      pdf.move_down 10
      pdf.text @data['yhtio']['osoite']
      pdf.move_down 10
      pdf.text @data['yhtio']['postino'] + ' ' + @data['yhtio']['postitp']
      pdf.move_down 5
      pdf.text @data['yhtio']['puhelin']

      pdf.move_up top_coordinate - pdf.cursor
      pdf.font 'Helvetica', :style => :normal, :size => 8

      pdf.indent(200) do
        @customer_data.each do |value|
          pdf.float do
            pdf.text value[:header], :style => :bold
          end
          pdf.indent(80) do
            pdf.text value[:value], :style => :normal
          end
          pdf.move_down 5
        end
      end

      pdf.move_up top_coordinate - pdf.cursor

      pdf.indent(400) do
        @spot_data.each do |value|
          pdf.float do
            pdf.text value[:header], :style => :bold
          end
          pdf.indent(80) do
            pdf.text value[:value], :style => :normal
          end
          pdf.move_down 5
        end
      end

      pdf.move_up top_coordinate - pdf.cursor

      pdf.indent(600) do
        @other_data.each do |value|
          pdf.float do
            pdf.text value[:header], :style => :bold
          end
          pdf.indent(80) do
            pdf.text value[:value], :style => :normal
          end
          pdf.move_down 5
        end
      end
    end
  end

  def rows(pdf)
    row_headers pdf

    pdf.move_down 10

    @data['rivit'].each do |row|
      row pdf, row
    end
  end

  def row_headers(pdf)
    #Line defines the drawing path. Stroke actually draws the line
    lines_cross_y = pdf.cursor
    pdf.line [pdf.bounds.left, lines_cross_y], [pdf.bounds.right, lines_cross_y]
    pdf.stroke

    pdf.font 'Helvetica', :size => 10
    pdf.move_down 10

    pdf.float do
      pdf.text 'Laitetiedot'
    end

    pdf.indent(650) do
      pdf.float do
        pdf.line [-7, lines_cross_y], [-7, pdf.bounds.bottom]
        pdf.stroke
        pdf.text 'Tehdyt toimenpiteet'
      end
    end

    pdf.font 'Helvetica', :size => 8
    x = pdf.bounds.left
    pdf.move_down 60


    pdf.float do
      pdf.move_down 5
      pdf.text_box 'Sijainti nro', :rotate => 90, :at => [x, pdf.cursor]
      pdf.move_up 5
      pdf.text_box 'Laitteen sijainti', :at => [x+30, pdf.cursor]
      pdf.move_down 5
      pdf.text_box 'Muuttunut sijainti', :at => [x+200, pdf.cursor], :rotate => 90
      pdf.move_up 5
      pdf.text_box 'Merkki / malli', :at => [x+220, pdf.cursor]

      pdf.move_up 10
      pdf.text_box 'Koko', :at => [x+320, pdf.cursor]
      pdf.move_down 10
      pdf.text_box 'kg / litra', :at => [x+320, pdf.cursor]

      pdf.move_up 10
      pdf.text_box 'Palo-/', :at => [x+370, pdf.cursor]
      pdf.move_down 10
      pdf.text_box 'teholuokka', :at => [x+370, pdf.cursor]

      pdf.text_box 'Sammute', :at => [x+420, pdf.cursor]
      pdf.text_box 'Säiliön nro', :at => [x+470, pdf.cursor]
      pdf.text_box 'Ponnep nro', :at => [x+540, pdf.cursor]
      pdf.move_down 5
      pdf.text_box 'Poikkeama raportti', :at => [x+600, pdf.cursor], :rotate => 90
      pdf.text_box 'Viimeinen painekoe', :at => [x+625, pdf.cursor], :rotate => 90
      pdf.text_box 'Tark. väli', :at => [x+655, pdf.cursor], :rotate => 90
      pdf.text_box 'Tarkastus', :at => [x+685, pdf.cursor], :rotate => 90
      pdf.text_box 'Huolto', :at => [x+715, pdf.cursor], :rotate => 90
      pdf.text_box 'Painekoe', :at => [x+745, pdf.cursor], :rotate => 90
    end

    pdf.move_down 10
    pdf.line [pdf.bounds.left, pdf.cursor], [pdf.bounds.right, pdf.cursor]
    pdf.stroke

  end

  def row(pdf, row)
    table_cells = [
        pdf.make_cell(:content => row['laite']['oma_numero']),
        pdf.make_cell(:content => row['laite']['sijainti']),
        pdf.make_cell(:content => '__'), #muuttunut sijainti
        pdf.make_cell(:content => row['laite']['nimitys']),
        pdf.make_cell(:content => row['laite']['sammutin_koko']),
        pdf.make_cell(:content => ' '), #teholuokka
        pdf.make_cell(:content => row['laite']['sammutin_tyyppi']),
        pdf.make_cell(:content => row['laite']['sarjanro']),
        pdf.make_cell(:content => ' '), #ponnop nro
        pdf.make_cell(:content => row['poikkeus']),
        pdf.make_cell(:content => row['laite']['viimeinen_painekoe']),
        pdf.make_cell(:content => row['toimenpiteen_huoltovali']),
        pdf.make_cell(:content => row['tarkastus'].nil? ? '' : row['tarkastus']),
        pdf.make_cell(:content => row['huolto'].nil? ? '' : row['huolto']),
        pdf.make_cell(:content => row['koeponnistus'].nil? ? '' : row['koeponnistus']),
    ]

    pdf.table([table_cells],
              :column_widths => {
                  0  => 30,
                  1  => 165,
                  2  => 20,
                  3  => 100,
                  4  => 50,
                  5  => 50,
                  6  => 55,
                  7  => 70,
                  8  => 50,
                  9  => 25,
                  10 => 30,
                  11 => 30,
                  12 => 30,
                  13 => 30,
                  14 => 30,
                  15 => 30,
              },
              :cell_style    => {
                  :borders => []
              })
  end

  def footer(pdf)
    x = 0
    y = 50

    pdf.line [pdf.bounds.left, y], [pdf.bounds.right, y]
    pdf.stroke_horizontal_line 1, 1, :at => y

    y -= 10
    pdf.draw_text "Pvm", :at => [x, y]

    x += 120
    pdf.line [x-5, 50], [x-5, 0]
    pdf.draw_text "Työn suorittajan kuittaus / nimen selvennys", :at => [x, y]


    x += 300
    pdf.line [x-5, 50], [x-5, 0]
    pdf.draw_text "Asiakkaan kuittaus / nimen selvennys", :at => [x, y]

    y = 0
    pdf.line [pdf.bounds.left, y], [pdf.bounds.right, y]
    pdf.stroke_horizontal_line 1, 1, :at => y
  end

  def header(pdf)
    pdf.repeat(:all, :dynamic => true) do
      pdf.draw_text pdf.page_number, :at => [770, 540]
      logo pdf

      pdf.move_down 40

      pdf.font 'Helvetica', :style => :bold, :size => 10
      pdf.draw_text "Tarkastuspöytäkirjan nro #{@data['tunnus']}", :at => [600, 515.28]
      pdf.font 'Helvetica', :style => :normal
    end
  end

  def logo(pdf)
    filepath = '/tmp/logo.jpeg'
    File.open(filepath, 'a+') { |file|
      file.write Base64.decode64 @logo
    }
    pdf.float do
      pdf.image filepath, :scale => 0.7, :at => [0, 555.28]
    end
  end

  def data=(data)
    @data = data
  end

end

class WorkOrderDAO

  attr_accessor :data

  def initialize(filepath)
    @data = JSON.load(File.read(filepath))
  end

  def data
    @data
  end
end

if !ARGV[0].empty?

  workorder = WorkOrderDAO.new(ARGV[0])

  pdf      = TarkastuspoytakirjaPDF.new
  pdf.data = workorder.data

  puts pdf.generate
else
  #error
  #exit
end