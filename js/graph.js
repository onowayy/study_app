import javax.swing.*;
import java.awt.*;

public class PieChartPanel extends JPanel {
    private final double[] values = {30, 20, 50};
    private final Color[] colors = {Color.RED, Color.GREEN, Color.BLUE};
    private final String[] labels = {"Apple", "Banana", "Cherry"};

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);
        Graphics2D g2 = (Graphics2D) g;
        int x = 50, y = 50, diameter = 300;
        double total = 0;
        for (double value : values) total += value;

        double startAngle = 0;
        for (int i = 0; i < values.length; i++) {
            double angle = values[i] / total * 360;
            g2.setColor(colors[i]);
            g2.fillArc(x, y, diameter, diameter, (int) Math.round(startAngle), (int) Math.round(angle));
            g2.setColor(Color.BLACK);
            g2.drawString(labels[i] + " (" + values[i] + "%)", x + diameter + 20, y + 20 + i * 20);
            startAngle += angle;
        }
    }

    public static void main(String[] args) {
        JFrame frame = new JFrame("Pie Chart Example");
        frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        frame.add(new PieChartPanel());
        frame.setSize(500, 450);
        frame.setLocationRelativeTo(null);
        frame.setVisible(true);
    }
}
