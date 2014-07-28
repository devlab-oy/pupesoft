#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

class TyolistaPDF
  @logo          = nil
  @customer_data = nil
  @spot_data     = nil
  @other_data    = nil

  @margin = nil
  @data   = nil
  @pdf = nil

  def initialize
    @margin = 20
  end

  def generate(_pdf, header_called, multi=false)
    init

    @pdf = _pdf
    @pdf.font 'Helvetica', :style => :normal, :size => 8
    header unless header_called
    @pdf.move_down 80
    info
    rows
    footer

    if multi === false
      filename = "Tyolista_#{@data['tunnus'].to_s}.pdf"
    else
      filename = "Tyolistat.pdf"
    end

  end

  def init
    @logo   = @data['logo']

    #Space needs to be added if nil. Otherwise text() doesnt work as wanted
    zipcode = @data['asiakas']['postino'].empty? ? ' ' : @data['asiakas']['postino']
    city    = @data['asiakas']['postitp'].empty? ? ' ' : @data['asiakas']['postitp']

    @customer_data = [
        {
            :header => 'Asiakas nro',
            :value  => @data['asiakas']['asiakasnro'].empty? ? ' ' : @data['asiakas']['asiakasnro']
        },
        {
            :header => 'Asiakas',
            :value  => @data['asiakas']['nimi'].empty? ? ' ' : @data['asiakas']['nimi']
        },
        {
            :header => 'Katuosoite',
            :value  => @data['asiakas']['osoite'].empty? ? ' ' : @data['asiakas']['osoite']
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
            :value  => @data['tunnus'].empty? ? ' ' : @data['asiakas']['tunnus']
        },
    ]

    zipcode = @data['kohde']['postino'].empty? ? ' ' : @data['kohde']['postino']
    city    = @data['kohde']['postitp'].empty? ? ' ' : @data['kohde']['postitp']

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

  def info
    @pdf.font 'Helvetica', :style => :bold, :size => 10
    @pdf.draw_text "Tyolistan nro #{@data['tunnus']}", :at => [600, 515.28]
    @pdf.bounding_box([@pdf.bounds.left, @pdf.cursor], :width => @pdf.bounds.right - @margin, :height => 115) do
      top_coordinate = @pdf.cursor
      @pdf.font 'Helvetica', :style => :normal, :size => 10

      @pdf.move_down 10
      @pdf.text @data['yhtio']['nimi']
      @pdf.move_down 5
      @pdf.text @data['yhtio']['osoite']
      @pdf.move_down 5
      @pdf.text @data['yhtio']['puhelin']

      @pdf.font 'Helvetica', :style => :normal, :size => 8

      @siirto = 105
      @customer_data.each do |value|
        if @pdf.width_of(value[:value]) > 150
          until @pdf.width_of(value[:value]) < 140 do
            value[:value].chop!
          end
          value[:value] = "#{value[:value]}..."
        end
        @pdf.text_box value[:header], :width => 75, :align => :right, :at => [140, @siirto], :style => :bold
        @pdf.text_box value[:value], :align => :left, :at => [220, @siirto]
        @siirto = @siirto - 13
      end

      @siirto = 105
      @spot_data.each do |value|
        if @pdf.width_of(value[:value]) > 150
          until @pdf.width_of(value[:value]) < 140 do
            value[:value].chop!
          end
          value[:value] = "#{value[:value]}..."
        end
        @pdf.text_box value[:header], :width => 75, :align => :right, :at => [390, @siirto], :style => :bold
        @pdf.text_box value[:value], :align => :left, :at => [470, @siirto]
        @siirto = @siirto - 13
      end

      @siirto = 105
      @other_data.each do |value|
        if @pdf.width_of(value[:value]) > 150
          until @pdf.width_of(value[:value]) < 148 do
            value[:value].chop!
          end
          value[:value] = "#{value[:value]}..."
        end
        @pdf.text_box value[:header], :width => 75, :align => :right, :at => [610, @siirto], :style => :bold
        @pdf.text_box value[:value], :align => :left, :at => [690, @siirto]
        @siirto = @siirto - 13
      end

    end
  end

  def rows
    row_headers
    @data['rivit'].each_with_index do |r,i|
      if @pdf.cursor < 50
        @pdf.start_new_page
        @pdf.move_down 90
        row_headers
      end
      if i > 0
        @pdf.transparent(0.2) do
          @pdf.stroke_horizontal_rule
        end
      end
      row(r)
    end
  end

  def row_headers
    #Line defines the drawing path. Stroke actually draws the line
    lines_cross_y = @pdf.cursor
    @pdf.line [@pdf.bounds.left, lines_cross_y], [@pdf.bounds.right, lines_cross_y]
    @pdf.stroke

    @pdf.font 'Helvetica', :size => 10
    @pdf.move_down 10

    @pdf.float do
      @pdf.text 'Laitetiedot'
    end

    @pdf.indent(650) do
      @pdf.float do
        @pdf.line [-7, lines_cross_y], [-7, @pdf.bounds.bottom]
        @pdf.stroke
        @pdf.text 'Tehdyt toimenpiteet'
      end
    end

    @pdf.font 'Helvetica', :size => 8
    x = @pdf.bounds.left
    @pdf.move_down 60

    @pdf.float do
      @pdf.move_down 5
      @pdf.text_box 'Sijainti nro', :rotate => 90, :at => [x, @pdf.cursor]
      @pdf.move_up 5
      @pdf.text_box 'Laitteen sijainti', :at => [x+30, @pdf.cursor]
      @pdf.move_down 5
      @pdf.text_box 'Muuttunut sijainti', :at => [x+150, @pdf.cursor], :rotate => 90
      @pdf.move_up 5
      @pdf.text_box 'Merkki / malli', :at => [x+170, @pdf.cursor]

      @pdf.move_up 10
      @pdf.text_box 'Koko', :at => [x+270, @pdf.cursor]
      @pdf.move_down 10
      @pdf.text_box 'kg / litra', :at => [x+270, @pdf.cursor]

      @pdf.move_up 10
      @pdf.text_box 'Palo-/', :at => [x+320, @pdf.cursor]
      @pdf.move_down 10
      @pdf.text_box 'teholuokka', :at => [x+320, @pdf.cursor]

      @pdf.text_box 'Sammute', :at => [x+390, @pdf.cursor]
      @pdf.text_box 'Säiliön nro', :at => [x+470, @pdf.cursor]
      @pdf.text_box 'Ponnep nro', :at => [x+540, @pdf.cursor]
      @pdf.move_down 5
      @pdf.text_box 'Poikkeama raportti', :at => [x+600, @pdf.cursor], :rotate => 90
      @pdf.text_box 'Viimeinen painekoe', :at => [x+625, @pdf.cursor], :rotate => 90
      @pdf.text_box 'Tark. väli', :at => [x+655, @pdf.cursor], :rotate => 90
      @pdf.text_box 'Tarkastus', :at => [x+685, @pdf.cursor], :rotate => 90
      @pdf.text_box 'Huolto', :at => [x+715, @pdf.cursor], :rotate => 90
      @pdf.text_box 'Painekoe', :at => [x+745, @pdf.cursor], :rotate => 90
    end

    @pdf.move_down 10
    @pdf.line [@pdf.bounds.left, @pdf.cursor], [@pdf.bounds.right, @pdf.cursor]
    @pdf.stroke

  end

  def row(row)
    table_cells = [
        @pdf.make_cell(:content => row['laite']['oma_numero']),
        @pdf.make_cell(:content => row['laite']['paikka_nimi']),
        @pdf.make_cell(:content => ' '), #muuttunut sijainti
        @pdf.make_cell(:content => row['laite']['nimitys']),
        @pdf.make_cell(:content => row['laite']['sammutin_koko']),
        @pdf.make_cell(:content => row['laite']['palo_luokka']), #teholuokka
        @pdf.make_cell(:content => row['laite']['sammutin_tyyppi']),
        @pdf.make_cell(:content => row['laite']['sarjanro']),
        @pdf.make_cell(:content => ' '), #ponnop nro
        @pdf.make_cell(:content => ' '), #poikkeus
        @pdf.make_cell(:content => row['laite']['viimeinen_painekoe']),
        @pdf.make_cell(:content => row['toimenpiteen_huoltovali']),
        @pdf.make_cell(:content => row['tarkastus']),
        @pdf.make_cell(:content => row['huolto']),
        @pdf.make_cell(:content => row['koeponnistus'])
    ]

    @pdf.table([table_cells],
              :column_widths => {
                  0  => 30,
                  1  => 115,
                  2  => 20,
                  3  => 100,
                  4  => 50,
                  5  => 70,
                  6  => 85,
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

    if row['kommentti'] != ''
      @pdf.text_box "Kommentti: #{row['kommentti']}", :at => [5, @pdf.cursor]
      @pdf.move_down 20
    end

  end

  def footer
    x = 0
    y = 50

    @pdf.line [@pdf.bounds.left, y], [@pdf.bounds.right, y]
    @pdf.stroke_horizontal_line 1, 1, :at => y

    y -= 10
    @pdf.draw_text "Pvm", :at => [x, y]

    x += 120
    @pdf.line [x-5, 50], [x-5, 0]
    @pdf.draw_text "Työn suorittajan kuittaus / nimen selvennys", :at => [x, y]


    x += 300
    @pdf.line [x-5, 50], [x-5, 0]
    @pdf.draw_text "Asiakkaan kuittaus / nimen selvennys", :at => [x, y]

    y = 0
    @pdf.line [@pdf.bounds.left, y], [@pdf.bounds.right, y]
    @pdf.stroke_horizontal_line 1, 1, :at => y
  end

  def header
    @pdf.repeat(:all, :dynamic => true) do
      @pdf.draw_text @pdf.page_number, :at => [770, 540]
      logo
      @pdf.move_down 40
    end
  end

  def logo
    filepath = '/tmp/logo.jpeg'
    File.open(filepath, 'a+') { |file|
      file.write Base64.decode64 @logo
    }
    @pdf.float do
      @pdf.image filepath, :width => 139, :height => 76, :at => [0, 555.28]
    end
  end

  def data=(data)
    @data = data
  end

end

if !ARGV[0].empty?

  @data = JSON.load(File.read(ARGV[0]))

      file          = ''
      margin        = 20
      _pdf          = Prawn::Document.new(:page_size   => 'A4',
                                          :page_layout => :landscape,
                                          :margin      => margin
      )


  if ( @data.class == Array )

      i             = 0
      header_called = false
      @data.each do |worklist|
        pdf          = TyolistaPDF.new
        pdf.data     = worklist

        file = pdf.generate _pdf, header_called, true

        header_called = true

        if i != @data.count - 1
          _pdf.start_new_page
        end
        i += 1
      end

      _pdf.render_file "/tmp/#{file}"
      puts file

  else
    pdf      = TyolistaPDF.new
    pdf.data = @data
    file = pdf.generate _pdf, nil
    _pdf.render_file "/tmp/#{file}"
    puts file
  end

end
