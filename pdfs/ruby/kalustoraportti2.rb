#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

class KalustoraporttiPDF2
  @logo     = nil
  @customer = nil
  @company  = nil

  @margin = nil
  @data   = nil

  def initialize
    @margin = 20
  end

  def generate(_pdf)
    if _pdf.nil?
      filepath = "/tmp/Kalustoraportti_#{@data['kohde_tunnus'].to_s}.pdf"
      filename = "Kalustoraportti_#{@data['kohde_tunnus'].to_s}.pdf"
      Prawn::Document.generate(filepath,
                               { :page_size   => 'A4',
                                 :page_layout => :landscape,
                                 :margin      => [@margin, @margin, @margin, @margin]
                               }) do |pdf|

        pdf.font 'Helvetica', :style => :normal, :size => 8
        header pdf

        info pdf

        filename
      end
    else
      _pdf.font 'Helvetica', :style => :normal, :size => 8
      header _pdf

      'Kalustoraportit.pdf'
    end
  end

  def header(pdf)
    pdf.repeat(:all, :dynamic => true) do
      pdf.draw_text pdf.page_number, :at => [770, 520]

      logo pdf

      y_temp = pdf.y

      company_info pdf

      pdf.move_up y_temp - pdf.y

      pdf.font 'Helvetica', :size => 8

      header_info pdf

      pdf.move_down 20

      spot_devices pdf
    end
  end

  def logo(pdf)
    filepath = '/tmp/logo.jpeg'
    File.open(filepath, 'a+') { |file|
      file.write Base64.decode64 @logo
    }
    pdf.image filepath, :scale => 0.7
  end

  def company_info(pdf)
    pdf.font 'Helvetica', :size => 10
    pdf.text @company['nimi']

    pdf.move_down 25
    pdf.text 'KALUSTORAPORTTI', :style => :bold
  end

  def header_info(pdf)
    y_temp        = pdf.y
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
            :value  => @customer['osoite'].empty? ? ' ' : @customer['osoite']
        },
    ]

    pdf.indent(200) do
      header_table(pdf, customer_data)
    end

    pdf.move_up y_temp - pdf.y

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

    pdf.indent(400) do
      header_table(pdf, other_data)
    end
  end

  def header_table(pdf, data)
    data.each do |value|
      pdf.float do
        pdf.text value[:header], :style => :bold
      end
      pdf.indent(100) do
        pdf.text value[:value], :style => :normal
      end
      pdf.move_down 10
    end
  end

  def spot_devices(pdf)
    row_headers pdf

    pdf.move_down 10

    rows pdf
  end

  def row_headers(pdf)
    horizontal_line pdf

    table_cells = [
        pdf.make_cell(:content => 'Järjestys nro'),
        pdf.make_cell(:content => 'Laitteen sijainti'),
        pdf.make_cell(:content => 'Nimike'), #muuttunut sijainti
        pdf.make_cell(:content => 'Säiliön nro'),
        pdf.make_cell(:content => 'Ponnep nro'),
        pdf.make_cell(:content => 'Sammute'), #teholuokka
        pdf.make_cell(:content => 'Palo-/teholuokka'),
        pdf.make_cell(:content => 'Valm. vuosi'),
        pdf.make_cell(:content => 'Tark. väli'), #ponnop nro
        pdf.make_cell(:content => 'Viimeinen tark kkvv'),
        pdf.make_cell(:content => 'huolto kkvv'),
        pdf.make_cell(:content => 'painekoe kkvv'),
        pdf.make_cell(:content => 'Poikk. raportti'),
    ]

    #pdf.table wants 2 dimensional table
    table pdf, [table_cells]

    horizontal_line pdf
  end

  def rows(pdf)

    table_cells = []
    @data['paikat'].each do |index, place|
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


        table_cells << [
            pdf.make_cell(:content => device['oma_numero']),
            pdf.make_cell(:content => device['sijainti']),
            pdf.make_cell(:content => device['tuote_nimi']),
            pdf.make_cell(:content => device['sarjanro']),
            pdf.make_cell(:content => device['ponnop_nro']),
            pdf.make_cell(:content => device['sammutin_tyyppi']),
            pdf.make_cell(:content => device['palo_luokka']),
            pdf.make_cell(:content => Date.parse(device['valm_pvm']).year.to_s),
            pdf.make_cell(:content => device['huoltovali']),
            pdf.make_cell(:content => tarkastus),
            pdf.make_cell(:content => huolto),
            pdf.make_cell(:content => koeponnistus),
            pdf.make_cell(:content => device['poikkeus']),
        ]
      end
    end

    table pdf, table_cells
  end

  def table(pdf, table_cells)
    pdf.table(table_cells,
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

  def horizontal_line(pdf)
    #Line defines the drawing path. Stroke actually draws the line
    lines_cross_y = pdf.cursor
    pdf.line [pdf.bounds.left, lines_cross_y], [pdf.bounds.right, lines_cross_y]
    pdf.stroke
  end

  def data=(data)
    @data = data
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

  file   = ''
  margin = 20
  _pdf   = Prawn::Document.new(:page_size   => 'A4',
                               :page_layout => :landscape,
                               :margin      => margin
  )
  i      = 0
  spots.data['kohteet'].each do |index, spot|
    pdf          = KalustoraporttiPDF2.new
    pdf.customer = spots.data['asiakas']
    pdf.company  = spots.data['yhtio']
    pdf.logo     = spots.data['logo']
    pdf.data     = spot

    file = pdf.generate _pdf

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