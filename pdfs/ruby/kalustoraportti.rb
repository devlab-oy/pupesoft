require 'rubygems'
require 'prawn'
require 'json'
require 'logger'


class KalustoraporttiPDF

  attr_accessor :customer, :company

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

    @x = 0
    @y = 560
    self.header

    @pdf.font 'Helvetica', :size => 10
    @y += 10
    self.print_spot_devices

    filepath = "/tmp/Kalustoraportti_" + @data['kohde_tunnus'].to_s + ".pdf"
    @pdf.render_file filepath

    return filepath
  end

  def header
    self.logo

    @y -= 90
    self.company_info
    @y += 35
    @pdf.font 'Helvetica', :size => 8
    @x = 200
    customer_data = [
      {
        :header => 'Asiakas nro',
        :value => @customer['asiakasnro']
      },
      {
        :header => 'Asiakas',
        :value => @customer['nimi']
      },
      {
        :header => 'Kohde',
        :value => @customer['osoite']
      },
    ]
    self.customer_info(customer_data)

    @x += 250
    @y += 45

    other_data = [
      {
        :header => 'Pvm',
        :value => 'tähän tämä päivä'
      },
      {
        :header => 'Asiakasvastaava',
        :value => '??'
      },
    ]
    self.other_info(other_data)
  end

  def logo
    @pdf.image "/Users/joonas/Dropbox/Devlab yleiset/Projektit/Turvata/safetyeasy dokumentaatiot/Raporttimallit/turvanasi_logo.png", :scale => 0.7, :at => [@x, @y]
  end

  def company_info
    @pdf.font 'Helvetica', :size => 10
    @pdf.draw_text @company['nimi'], :at => [@x, @y]

    @y -= 35
    @pdf.draw_text 'KALUSTORAPORTTI', :at => [@x, @y], :style => :bold
  end

  def customer_info(customer_data)
    customer_data.each do |value|
      @pdf.draw_text value[:header], :style => :bold, :at => [@x, @y]
      @pdf.draw_text value[:value], :style => :normal, :at => [@x+100, @y]

      @y -= 15      
    end
  end

  def other_info(other_data)
    other_data.each do |value|
      @pdf.draw_text value[:header], :style => :bold, :at => [@x, @y]
      @pdf.draw_text value[:value], :style => :normal, :at => [@x+100, @y]

      @y -= 30
    end
  end

  def print_spot_devices
    @pdf.line [0, @y], [@pdf.bounds.right, @y]
    @pdf.stroke_horizontal_line 1, 1, :at => @y

    @pdf.font 'Helvetica', :size => 8
    self.print_row_headers

    @y -= 15
    @pdf.line [0, @y], [@pdf.bounds.right, @y]
    @pdf.stroke_horizontal_line 1, 1, :at => @y

    @data['paikat'].each do |place|
      place['laitteet'].each do |device|
        self.print_row row
      end
    end
  end

  def print_row_headers
    @x = 0
    @y -= 20
    @pdf.bounding_box([@x, @y+10], :width => 35, :height => 50) do
      @pdf.text 'Järjestys nro'
    end

    @x += 40
    @pdf.draw_text 'Laitteen sijainti', :at => [@x, @y]

    @x += 200
    @pdf.draw_text "Nimike", :at => [@x, @y]

    @x += 70
    @pdf.draw_text "Säilion nro", :at => [@x, @y]

    @x += 50
    @pdf.draw_text "Ponnep nro", :at => [@x, @y]

    @x += 50
    @pdf.draw_text "Sammute", :at => [@x, @y]

    @x += 70
    @pdf.draw_text "Palo-/teholuokka", :at => [@x, @y]

    @x += 90
    @pdf.bounding_box([@x, @y+10], :width => 35, :height => 50) do
      @pdf.text "Valm. vuosi"
    end

    @x += 30
    @pdf.bounding_box([@x, @y+10], :width => 35, :height => 50) do
      @pdf.text "Tark. väli"
    end

    @x += 50
    @pdf.draw_text "Viimeinen", :at => [@x, @y+10]

    @y -= 5
    @pdf.bounding_box([@x, @y+10], :width => 35, :height => 50) do
      @pdf.text "tark kkvv"
    end

    @x += 40
    @pdf.bounding_box([@x, @y+10], :width => 35, :height => 50) do
      @pdf.text "huolto kkvv"
    end

    @x += 40
    @pdf.bounding_box([@x, @y+10], :width => 35, :height => 50) do
      @pdf.text "painekoe vuosi"
    end

    @x += 50
    @pdf.bounding_box([@x, @y+10], :width => 35, :height => 50) do
      @pdf.text "Poikk. raportti"
    end
  end

  def print_rows(row)
    @x = 0
    @pdf.draw_text row['oma_numero'], :at => [@x, @y]

    @x += 40
    @pdf.draw_text row['sijainti'], :at => [@x, @y]

    @x += 200
    @pdf.draw_text row['tuote_nimi'], :at => [@x, @y]

    @x += 70
    @pdf.draw_text row['sarjanro'], :at => [@x, @y]

    @x += 50
    @pdf.draw_text row['ponnep_nro'], :at => [@x, @y]

    @x += 50
    @pdf.draw_text row['tuote_tyyppi'], :at => [@x, @y]

    @x += 70
    @pdf.draw_text row['palo_luokka'], :at => [@x, @y]

    @x += 90
    @pdf.draw_text row['valm_pvm'], :at => [@x, @y]

    @x += 30
    @pdf.draw_text row['tarkastus_vali'], :at => [@x, @y]

    @pdf.draw_text row['tark kkvv'], :at => [@x, @y]

    @x += 40
    @pdf.draw_text row['huolto kkvv'], :at => [@x, @y]

    @x += 40
    @pdf.draw_text row['painekoe_vuosi'], :at => [@x, @y]

    @x += 50
    @pdf.draw_text row['poikkeama_raportti'], :at => [@x, @y]
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

  spots.data['kohteet'].each do |index, spot|
    pdf = KalustoraporttiPDF.new
    pdf.customer = spots.data['asiakas']
    pdf.company = spots.data['yhtio']
    pdf.data(spot)

    puts pdf.generate
  end
else
  #error
  #exit
end
