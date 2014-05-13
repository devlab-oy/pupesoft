#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

class KalustoraporttiPDF
  @logo     = nil
  @customer = nil
  @company  = nil

  @margin = nil
  @spot   = nil

  @pdf = nil

  def initialize
    @margin = 20
  end

  def generate(_pdf, header_called)
    if _pdf.nil?
      filepath = "/tmp/Kalustoraportti_#{@spot['kohde_tunnus'].to_s}.pdf"
      filename = "Kalustoraportti_#{@spot['kohde_tunnus'].to_s}.pdf"
      Prawn::Document.generate(filepath,
                               { :page_size   => 'A4',
                                 :page_layout => :landscape,
                                 :margin      => [100, @margin, @margin, @margin]
                               }) do |pdf|
        @pdf = pdf
        @pdf.font 'Helvetica', :style => :normal, :size => 8
        header

        @pdf.move_down 75
        @pdf.font 'Helvetica', :size => 8

        header_info

        @pdf.move_down 20

        spot_devices

        filename
      end
    else
      @pdf = _pdf
      @pdf.font 'Helvetica', :style => :normal, :size => 8
      header unless header_called

      @pdf.move_down 75
      @pdf.font 'Helvetica', :size => 8

      header_info

      @pdf.move_down 20

      spot_devices

      'Kalustoraportit.pdf'
    end
  end

  def header
    @pdf.repeat(:all, :dynamic => true) do
      @pdf.draw_text @pdf.page_number, :at => [770, 520]

      logo

      company_info
    end
  end

  def logo
    filepath = '/tmp/logo.jpeg'
    File.open(filepath, 'a+') { |file|
      file.write Base64.decode64 @logo
    }
    @pdf.image filepath, :width => 139, :height => 76
  end

  def company_info
    @pdf.font 'Helvetica', :size => 10
    @pdf.text @company['nimi']

    @pdf.move_down 25
    @pdf.text 'KALUSTORAPORTTI', :style => :bold
  end

  def header_info
    y_temp        = @pdf.y
    customer_data = [
        {
            :header => 'Asiakas nro',
            :value  => @customer['asiakasnro'].empty? ? ' ' : @customer['asiakasnro']
        },
        {
            :header => 'Asiakas',
            :value  => @customer['nimi'].empty? ? ' ' : @customer['nimi']
        },
        {
            :header => 'Kohde',
            :value  => @spot['kohde_nimi'].empty? ? ' ' : @spot['kohde_nimi']
        },
    ]

    @pdf.indent(200) do
      header_table(customer_data)
    end

    @pdf.move_up y_temp - @pdf.y

    other_data = [
        {
            :header => 'Pvm',
            :value  => DateTime.now.strftime('%d.%m.%Y')
        },
        {
            :header => 'Asiakasvastaava',
            :value  => ' '
        },
    ]

    @pdf.indent(400) do
      header_table(other_data)
    end
  end

  def header_table(data)
    data.each do |value|
      @pdf.float do
        @pdf.text value[:header], :style => :bold
      end
      @pdf.indent(100) do
        @pdf.text value[:value], :style => :normal
      end
      @pdf.move_down 10
    end
  end

  def spot_devices
    row_headers

    @pdf.move_down 10

    rows
  end

  def row_headers
    horizontal_line

    table_cells = [
        @pdf.make_cell(:content => 'Järjestys nro'),
        @pdf.make_cell(:content => 'Laitteen sijainti'),
        @pdf.make_cell(:content => 'Nimike'), #muuttunut sijainti
        @pdf.make_cell(:content => 'Säiliön nro'),
        @pdf.make_cell(:content => 'Ponnep nro'),
        @pdf.make_cell(:content => 'Sammute'), #teholuokka
        @pdf.make_cell(:content => 'Palo-/teholuokka'),
        @pdf.make_cell(:content => 'Valm. vuosi'),
        @pdf.make_cell(:content => 'Tark. väli'), #ponnop nro
        @pdf.make_cell(:content => 'Viimeinen tark kkvv'),
        @pdf.make_cell(:content => 'huolto kkvv'),
        @pdf.make_cell(:content => 'painekoe kkvv'),
        @pdf.make_cell(:content => 'Poikk. raportti'),
    ]

    #@pdf.table wants 2 dimensional table
    table [table_cells]

    horizontal_line
  end

  def rows
    table_cells = []
    @spot['paikat'].each do |index, place|
      place['laitteet'].each do |device|

        begin
          Date.parse(device['viimeiset_tapahtumat']['tarkastus'])
          tarkastus = Date.parse(device['viimeiset_tapahtumat']['tarkastus']).strftime('%m%y')
        rescue
        end

        begin
          Date.parse(device['viimeiset_tapahtumat']['huolto'])
          huolto = Date.parse(device['viimeiset_tapahtumat']['huolto']).strftime('%m%y')
        rescue
        end

        begin
          Date.parse(device['viimeiset_tapahtumat']['koeponnistus'])
          koeponnistus = Date.parse(device['viimeiset_tapahtumat']['koeponnistus']).strftime('%m%y')
        rescue
        end

        begin
          valmistus_paiva_year = Date.parse(device['valm_pvm']).year.to_s
        rescue
          valmistus_paiva_year = ' '
        end

        table_cells << [
            device['oma_numero'],
            device['paikka_nimi'],
            device['tuote_nimi'],
            device['sarjanro'],
            device['ponnop_nro'],
            device['sammutin_tyyppi'],
            device['palo_luokka'],
            valmistus_paiva_year,
            device['huoltovali'],
            tarkastus,
            huolto,
            koeponnistus,
            device['poikkeus']
        ]
      end
    end

    table table_cells
  end

  def table(table_cells)
    @pdf.table(table_cells,
               :column_widths => {
                   0  => 45,
                   1  => 175,
                   2  => 70,
                   3  => 50,
                   4  => 50,
                   5  => 70,
                   6  => 90,
                   7  => 30,
                   8  => 40,
                   9  => 50,
                   10 => 40,
                   11 => 50,
                   12 => 40,
               },
               :cell_style    => {
                   :borders => []
               })
  end

  def horizontal_line
    #Line defines the drawing path. Stroke actually draws the line
    lines_cross_y = @pdf.cursor
    @pdf.line [@pdf.bounds.left, lines_cross_y], [@pdf.bounds.right, lines_cross_y]
    @pdf.stroke
  end

  def spot=(spot)
    @spot = spot
  end

  def customer=(customer)
    @customer = customer
  end

  def company=(company)
    @company = company
  end

  def logo=(logo)
    @logo = logo
  end
end

class SpotDAO

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

  spots = SpotDAO.new(ARGV[0])

  file          = ''
  margin        = 20
  _pdf          = Prawn::Document.new(:page_size   => 'A4',
                                      :page_layout => :landscape,
                                      :margin      => margin
  )
  i             = 0
  header_called = false
  spots.data['kohteet'].each do |index, spot|
    pdf          = KalustoraporttiPDF.new
    pdf.customer = spots.data['asiakas']
    pdf.company  = spots.data['yhtio']
    pdf.logo     = spots.data['logo']
    pdf.spot     = spot

    file = pdf.generate _pdf, header_called

    header_called = true

    if i != spots.data['kohteet'].count - 1
      _pdf.start_new_page
    end
    i += 1
  end

  _pdf.render_file "/tmp/#{file}"
  puts file
else
  puts 'argv0 is empty'
end
