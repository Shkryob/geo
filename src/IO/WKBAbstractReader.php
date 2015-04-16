<?php

namespace Brick\Geo\IO;

use Brick\Geo\CoordinateSystem;
use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Exception\GeometryParseException;
use Brick\Geo\Geometry;
use Brick\Geo\Point;
use Brick\Geo\LineString;
use Brick\Geo\CircularString;
use Brick\Geo\CompoundCurve;
use Brick\Geo\Polygon;
use Brick\Geo\CurvePolygon;
use Brick\Geo\MultiPoint;
use Brick\Geo\MultiLineString;
use Brick\Geo\MultiPolygon;
use Brick\Geo\GeometryCollection;
use Brick\Geo\PolyhedralSurface;
use Brick\Geo\TIN;
use Brick\Geo\Triangle;

/**
 * Base class for WKBReader and EWKBReader.
 */
abstract class WKBAbstractReader
{
    /**
     * @param WKBBuffer $buffer       The WKB buffer.
     * @param integer   $geometryType A variable to store the geometry type.
     * @param boolean   $is3D         A variable to store whether the geometry has Z coordinates.
     * @param boolean   $isMeasured   A variable to store whether the geometry has M coordinates.
     * @param integer   $srid         A variable to store the SRID.
     *
     * @return void
     *
     * @throws GeometryException
     */
    abstract protected function readGeometryHeader(WKBBuffer $buffer, & $geometryType, & $is3D, & $isMeasured, & $srid);

    /**
     * @param WKBBuffer $buffer
     * @param integer   $srid
     *
     * @return Geometry
     *
     * @throws GeometryParseException
     */
    protected function readGeometry(WKBBuffer $buffer, $srid)
    {
        $buffer->readByteOrder();

        $this->readGeometryHeader($buffer, $geometryType, $is3D, $isMeasured, $srid);

        $cs = CoordinateSystem::create($is3D, $isMeasured, $srid);

        switch ($geometryType) {
            case Geometry::POINT:
                return $this->readPoint($buffer, $cs);

            case Geometry::LINESTRING:
                return $this->readLineString($buffer, $cs);

            case Geometry::CIRCULARSTRING:
                return $this->readCircularString($buffer, $cs);

            case Geometry::COMPOUNDCURVE:
                return $this->readCompoundCurve($buffer, $cs);

            case Geometry::POLYGON:
                return $this->readPolygon($buffer, $cs);

            case Geometry::CURVEPOLYGON:
                return $this->readCurvePolygon($buffer, $cs);

            case Geometry::MULTIPOINT:
                return $this->readMultiPoint($buffer, $cs);

            case Geometry::MULTILINESTRING:
                return $this->readMultiLineString($buffer, $cs);

            case Geometry::MULTIPOLYGON:
                return $this->readMultiPolygon($buffer, $cs);

            case Geometry::GEOMETRYCOLLECTION:
                return $this->readGeometryCollection($buffer, $cs);

            case Geometry::POLYHEDRALSURFACE:
                return $this->readPolyhedralSurface($buffer, $cs);

            case Geometry::TIN:
                return $this->readTIN($buffer, $cs);

            case Geometry::TRIANGLE:
                return $this->readTriangle($buffer, $cs);
        }

        throw GeometryParseException::unsupportedGeometryType($geometryType);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\Point
     */
    private function readPoint(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $coords = $buffer->readDoubles($cs->coordinateDimension());

        return Point::create(array_values($coords), $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\LineString
     */
    private function readLineString(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numPoints = $buffer->readUnsignedLong();

        $points = [];

        for ($i = 0; $i < $numPoints; $i++) {
            $points[] = $this->readPoint($buffer, $cs);
        }

        return LineString::create($points, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\LineString
     */
    private function readCircularString(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numPoints = $buffer->readUnsignedLong();

        $points = [];

        for ($i = 0; $i < $numPoints; $i++) {
            $points[] = $this->readPoint($buffer, $cs);
        }

        return CircularString::create($points, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\CompoundCurve
     */
    private function readCompoundCurve(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numCurves = $buffer->readUnsignedLong();
        $curves = [];

        for ($i = 0; $i < $numCurves; $i++) {
            $curves[] = $this->readGeometry($buffer, $cs->SRID());
        }

        return CompoundCurve::create($curves, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\Polygon
     */
    private function readPolygon(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numRings = $buffer->readUnsignedLong();

        $rings = [];

        for ($i = 0; $i < $numRings; $i++) {
            $rings[] = $this->readLineString($buffer, $cs);
        }

        return Polygon::create($rings, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\CurvePolygon
     */
    private function readCurvePolygon(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numRings = $buffer->readUnsignedLong();

        $rings = [];

        for ($i = 0; $i < $numRings; $i++) {
            $rings[] = $this->readGeometry($buffer, $cs->SRID());
        }

        return CurvePolygon::create($rings, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\MultiPoint
     */
    private function readMultiPoint(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numPoints = $buffer->readUnsignedLong();
        $points = [];

        for ($i = 0; $i < $numPoints; $i++) {
            $points[] = $this->readGeometry($buffer, $cs->SRID());
        }

        return MultiPoint::create($points, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\MultiLineString
     */
    private function readMultiLineString(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numLineStrings = $buffer->readUnsignedLong();
        $lineStrings = [];

        for ($i = 0; $i < $numLineStrings; $i++) {
            $lineStrings[] = $this->readGeometry($buffer, $cs->SRID());
        }

        return MultiLineString::create($lineStrings, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\MultiPolygon
     */
    private function readMultiPolygon(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numPolygons = $buffer->readUnsignedLong();
        $polygons = [];

        for ($i = 0; $i < $numPolygons; $i++) {
            $polygons[] = $this->readGeometry($buffer, $cs->SRID());
        }

        return MultiPolygon::create($polygons, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\GeometryCollection
     */
    private function readGeometryCollection(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numGeometries = $buffer->readUnsignedLong();
        $geometries = [];

        for ($i = 0; $i < $numGeometries; $i++) {
            $geometries[] = $this->readGeometry($buffer, $cs->SRID());
        }

        return GeometryCollection::create($geometries, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\PolyhedralSurface
     */
    private function readPolyhedralSurface(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numPatches = $buffer->readUnsignedLong();
        $patches = [];

        for ($i = 0; $i < $numPatches; $i++) {
            $patches[] = $this->readGeometry($buffer, $cs->SRID());
        }

        return PolyhedralSurface::create($patches, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\TIN
     */
    private function readTIN(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numPatches = $buffer->readUnsignedLong();
        $patches = [];

        for ($i = 0; $i < $numPatches; $i++) {
            $patches[] = $this->readGeometry($buffer, $cs->SRID());
        }

        return TIN::create($patches, $cs);
    }

    /**
     * @param WKBBuffer        $buffer
     * @param CoordinateSystem $cs
     *
     * @return \Brick\Geo\Triangle
     */
    private function readTriangle(WKBBuffer $buffer, CoordinateSystem $cs)
    {
        $numRings = $buffer->readUnsignedLong();

        $rings = [];

        for ($i = 0; $i < $numRings; $i++) {
            $rings[] = $this->readLineString($buffer, $cs);
        }

        return Triangle::create($rings, $cs);
    }
}
