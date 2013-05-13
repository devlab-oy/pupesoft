require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'

class KalustoraporttiPDF

  attr_accessor :customer, :company

  def initialize
    margin = 20
    @pdf = Prawn::Document.new(:page_size => "A4", :page_layout => :landscape, :margin => [margin, margin, margin, margin])

    @pdf.font 'Helvetica', :style => :normal, :size => 10

    #NOTICE!! We have to use pdf_x and pdf_y because Prawn has x and y variables in it and they mix with this class x and y if we had used them in @pdf.repeat
    @pdf.repeat(:all, :dynamic => true) do
      @pdf.draw_text @pdf.page_number, :at => [770, 520]

      @pdf_x = 0
      @pdf_y = 560
      self.header
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

    #NOTICE!!! We have to manually set the y to correct spot because self.header gets called at @pdf.render_file position because of this we do not know how long the header is
    @x = 0
    @y = 410

    @pdf.font 'Helvetica', :size => 10
    @y += 10
    self.print_spot_devices

    filepath = "/tmp/Kalustoraportti_" + @data['kohde_tunnus'].to_s + ".pdf"
    #Filename is a separate variable because pdf.render_file wants full path but in HTML save form we want to force the directory user is able to download files from. this is the reason we only retrun filename
    filename = "Kalustoraportti_" + @data['kohde_tunnus'].to_s + ".pdf";
    @pdf.render_file filepath

    return filename
  end

  def header
    self.logo

    @pdf_y -= 90
    self.company_info
    @pdf_y += 35
    @pdf.font 'Helvetica', :size => 8
    @pdf_x = 200
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

    @pdf_x += 250
    @pdf_y += 45

    other_data = [
      {
        :header => 'Pvm',
        :value => DateTime.now.strftime('%d.%m.%Y')
      },
      {
        :header => 'Asiakasvastaava',
        :value => '??'
      },
    ]
    self.other_info(other_data)
  end

  def logo
    @pdf.image "/Users/joonas/Dropbox/Devlab yleiset/Projektit/Turvata/safetyeasy dokumentaatiot/Raporttimallit/turvanasi_logo.png", :scale => 0.7, :at => [@pdf_x, @pdf_y]
  end

  def company_info
    @pdf.font 'Helvetica', :size => 10
    @pdf.draw_text @company['nimi'], :at => [@pdf_x, @pdf_y]

    @pdf_y -= 35
    @pdf.draw_text 'KALUSTORAPORTTI', :at => [@pdf_x, @pdf_y], :style => :bold
  end

  def customer_info(customer_data)
    customer_data.each do |value|
      @pdf.draw_text value[:header], :style => :bold, :at => [@pdf_x, @pdf_y]
      @pdf.draw_text value[:value], :style => :normal, :at => [@pdf_x+100, @pdf_y]

      @pdf_y -= 15      
    end
  end

  def other_info(other_data)
    other_data.each do |value|
      @pdf.draw_text value[:header], :style => :bold, :at => [@pdf_x, @pdf_y]
      @pdf.draw_text value[:value], :style => :normal, :at => [@pdf_x+100, @pdf_y]

      @pdf_y -= 30
    end
  end

  def print_spot_devices
    @devices_start_temp = @y
    @pdf.font 'Helvetica', :size => 8
    self.print_row_headers


    @current_page = @pdf.page_count

    # @roll = @pdf.transaction do
    #   @y -= 30
    #   @devices_start_temp = @y
    #   @data['paikat'].each do |index, place|
    #     place['laitteet'].each do |device|
    #       self.print_row device
    #       @y -= 20
    #       @pdf.move_down 20
    #       @pdf.text ' '
    #     end
    #   end

    #   @pdf.rollback if @pdf.page_count > @current_page
    # end

    # if @roll == false
    #   @pdf.start_new_page

    #   @pdf.text 'TETETETETETETEETTE'
    # end

    @data['paikat'].each do |index, place|
      place['laitteet'].each do |device|
        self.print_row device
        @y -= 20
        # @pdf.move_down 20
        # @pdf.text @pdf.cursor.to_s

        if @y <= 20
          page_changes = true
        end

        if @pdf.page_count > @current_page
          page_changes = true
        end
        if page_changes
          @pdf.start_new_page
          @y = @devices_start_temp
          self.print_row_headers
          page_changes = false
          @current_page = @pdf.page_count
        end
      end
    end
  end

  def print_row_headers
    @pdf.line [0, @y], [@pdf.bounds.right, @y]
    @pdf.stroke_horizontal_line 1, 1, :at => @y

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

    @y -= 15
    @pdf.line [0, @y], [@pdf.bounds.right, @y]
    @pdf.stroke_horizontal_line 1, 1, :at => @y

    @y -= 30
  end

  def print_row(row)
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
    @pdf.draw_text row['sammutin_tyyppi'], :at => [@x, @y]

    @x += 70
    @pdf.draw_text row['palo_luokka'], :at => [@x, @y]

    @x += 90
    begin Date.parse(row['valm_pvm'])
      @pdf.draw_text Date.parse(row['valm_pvm']).year, :at => [@x, @y]
    rescue

    end

    @x += 35
    @pdf.draw_text row['huoltovali'], :at => [@x, @y]

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

  files = ''
  spots.data['kohteet'].each do |index, spot|
    pdf = KalustoraporttiPDF.new
    pdf.customer = spots.data['asiakas']
    pdf.company = spots.data['yhtio']
    pdf.data(spot)

    files += pdf.generate + ' '
  end
  puts files
else
  #error
  #exit
end
